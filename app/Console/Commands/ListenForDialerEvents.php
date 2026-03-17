<?php

namespace App\Console\Commands;

use App\Services\FreeswitchEslService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\Dialer\Services\DialerService;

class ListenForDialerEvents extends Command
{
    protected $signature = 'esl:listen-dialer';

    protected $description = 'Listen for dialer call progress, hangup, and AVMD events via ESL';

    public function handle(DialerService $dialerService, FreeswitchEslService $eslService): int
    {
        $this->info('Starting dialer ESL listener');

        $subscribed = false;

        while (true) {
            try {
                if (! $eslService->isConnected()) {
                    $this->warn('Dialer ESL disconnected. Reconnecting...');
                    $eslService->reconnect();
                    $subscribed = false;
                }

                if (! $subscribed) {
                    if (! $eslService->subscribeToEvents('plain', 'CHANNEL_ANSWER CHANNEL_HANGUP_COMPLETE CUSTOM')) {
                        $this->error('Failed to subscribe to dialer ESL events.');
                        sleep(5);

                        continue;
                    }

                    $subscribed = true;
                    $this->info('Subscribed to CHANNEL_ANSWER, CHANNEL_HANGUP_COMPLETE, and CUSTOM events.');
                }

                $eslService->listen(function ($event) use ($dialerService) {
                    $eventName = (string) $event->getHeader('Event-Name');
                    $subclass = strtolower((string) $event->getHeader('Event-Subclass'));

                    if ($eventName === 'CHANNEL_ANSWER') {
                        $this->handleAnswerEvent($dialerService, $event);
                        return;
                    }

                    if ($eventName === 'CHANNEL_HANGUP_COMPLETE') {
                        $this->handleHangupEvent($dialerService, $event);
                        return;
                    }

                    if ($eventName === 'CUSTOM' && str_contains($subclass, 'avmd::beep')) {
                        $this->handleAvmdBeepEvent($dialerService, $event);
                    }
                });
            } catch (\Throwable $error) {
                logger()->error('Dialer ESL listener crashed: ' . $error->getMessage() . ' at ' . $error->getFile() . ':' . $error->getLine());
                sleep(10);
            }
        }
    }

    protected function handleAnswerEvent(DialerService $dialerService, mixed $event): void
    {
        $attemptUuid = $this->getEventHeader($event, 'variable_dialer_attempt_uuid');

        if (! $attemptUuid) {
            return;
        }

        $dialerService->syncAttemptFromPayload([
            'event' => 'dialer_attempt_progress',
            'dialer_attempt_uuid' => $attemptUuid,
            'call_uuid' => $this->getEventHeader($event, 'Unique-ID'),
            'answered_at' => $this->resolveEventTimestamp($event),
            'metadata' => $this->buildMetadata($event),
        ]);
    }

    protected function handleHangupEvent(DialerService $dialerService, mixed $event): void
    {
        $attemptUuid = $this->getEventHeader($event, 'variable_dialer_attempt_uuid');

        if (! $attemptUuid) {
            return;
        }

        $dialerService->syncAttemptFromPayload([
            'event' => 'dialer_attempt_completed',
            'dialer_attempt_uuid' => $attemptUuid,
            'call_uuid' => $this->getEventHeader($event, 'Unique-ID'),
            'answered_at' => $this->resolveAnsweredAt($event),
            'completed_at' => $this->resolveEventTimestamp($event),
            'hangup_cause' => $this->getEventHeader($event, 'Hangup-Cause'),
            'talk_seconds' => $this->resolveSeconds($event, 'variable_billsec', 'variable_billmsec'),
            'wait_seconds' => $this->resolveSeconds($event, 'variable_progresssec', 'variable_progressmsec'),
            'metadata' => $this->buildMetadata($event),
        ]);
    }

    protected function handleAvmdBeepEvent(DialerService $dialerService, mixed $event): void
    {
        $payload = [
            'event' => 'dialer_amd_signal',
            'dialer_attempt_uuid' => $this->getEventHeader($event, 'variable_dialer_attempt_uuid'),
            'call_uuid' => $this->getEventHeader($event, 'Unique-ID'),
            'amd_result' => 'voicemail',
            'metadata' => $this->buildMetadata($event),
        ];

        $attempt = $dialerService->syncAttemptFromPayload($payload);

        if (! $attempt) {
            return;
        }

        $attempt->loadMissing('campaign');

        if ($attempt->campaign?->voicemail_action !== 'hangup' || ! $attempt->call_uuid) {
            return;
        }

        $response = (new FreeswitchEslService())->executeCommand('uuid_kill ' . $attempt->call_uuid);

        if (is_string($response) && str_starts_with($response, '-ERR')) {
            logger()->warning('Dialer AVMD hangup failed for call ' . $attempt->call_uuid . ': ' . $response);
        }
    }

    protected function resolveEventTimestamp(mixed $event): string
    {
        $rawTimestamp = $this->getEventHeader($event, 'Event-Date-Timestamp');

        if (is_numeric($rawTimestamp) && (int) $rawTimestamp > 0) {
            return CarbonImmutable::createFromTimestampUTC((int) floor(((int) $rawTimestamp) / 1000000))
                ->toIso8601String();
        }

        $rawDate = $this->getEventHeader($event, 'Event-Date-GMT')
            ?: $this->getEventHeader($event, 'Event-Date-Local');

        if ($rawDate) {
            try {
                return CarbonImmutable::parse($rawDate)->toIso8601String();
            } catch (\Throwable) {
            }
        }

        return now()->toIso8601String();
    }

    protected function resolveAnsweredAt(mixed $event): ?string
    {
        foreach (['variable_answer_stamp', 'Caller-Channel-Answered-Time'] as $header) {
            $value = $this->getEventHeader($event, $header);

            if (! $value) {
                continue;
            }

            if (is_numeric($value) && (int) $value > 0) {
                return CarbonImmutable::createFromTimestampUTC((int) floor(((int) $value) / 1000000))
                    ->toIso8601String();
            }

            try {
                return CarbonImmutable::parse($value)->toIso8601String();
            } catch (\Throwable) {
            }
        }

        return null;
    }

    protected function resolveSeconds(mixed $event, string $secondsHeader, string $millisecondsHeader): int
    {
        $seconds = $this->getEventHeader($event, $secondsHeader);

        if (is_numeric($seconds)) {
            return max(0, (int) $seconds);
        }

        $milliseconds = $this->getEventHeader($event, $millisecondsHeader);

        if (is_numeric($milliseconds)) {
            return max(0, (int) floor(((int) $milliseconds) / 1000));
        }

        return 0;
    }

    protected function buildMetadata(mixed $event): array
    {
        return array_filter([
            'event_name' => $this->getEventHeader($event, 'Event-Name'),
            'event_subclass' => $this->getEventHeader($event, 'Event-Subclass'),
            'dialer_campaign_uuid' => $this->getEventHeader($event, 'variable_dialer_campaign_uuid'),
            'dialer_lead_uuid' => $this->getEventHeader($event, 'variable_dialer_lead_uuid'),
            'dialer_mode' => $this->getEventHeader($event, 'variable_dialer_mode'),
            'dialer_amd_strategy' => $this->getEventHeader($event, 'variable_dialer_amd_strategy'),
            'hangup_cause' => $this->getEventHeader($event, 'Hangup-Cause'),
        ], static fn ($value) => $value !== null && $value !== '');
    }

    protected function getEventHeader(mixed $event, string $name): ?string
    {
        $value = $event?->getHeader($name);

        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
