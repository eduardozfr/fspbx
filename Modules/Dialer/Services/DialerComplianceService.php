<?php

namespace Modules\Dialer\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class DialerComplianceService
{
    public const DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public function defaultSchedule(): array
    {
        return collect(self::DAYS)->mapWithKeys(fn(string $day) => [
            $day => [
                'enabled' => true,
                'start' => '08:00',
                'end' => '20:00',
            ],
        ])->all();
    }

    public function normalizeSchedule(array|string|null $schedule): array
    {
        if (is_string($schedule) && $schedule !== '') {
            $decoded = json_decode($schedule, true);
            $schedule = is_array($decoded) ? $decoded : [];
        }

        $schedule = is_array($schedule) ? $schedule : [];
        $defaults = $this->defaultSchedule();

        foreach (self::DAYS as $day) {
            $row = $schedule[$day] ?? [];
            $defaults[$day] = [
                'enabled' => (bool) ($row['enabled'] ?? $defaults[$day]['enabled']),
                'start' => $row['start'] ?? $defaults[$day]['start'],
                'end' => $row['end'] ?? $defaults[$day]['end'],
            ];
        }

        return $defaults;
    }

    public function evaluate(array|string|null $schedule, string $timezone, ?CarbonInterface $moment = null): array
    {
        $normalized = $this->normalizeSchedule($schedule);
        $moment = $this->toImmutable($moment, $timezone);
        $day = strtolower($moment->englishDayOfWeek);
        $window = $normalized[$day] ?? ['enabled' => false, 'start' => null, 'end' => null];

        $start = $this->windowAt($moment, $window['start']);
        $end = $this->windowAt($moment, $window['end']);

        $allowed = (bool) ($window['enabled'] ?? false)
            && $start
            && $end
            && $moment->greaterThanOrEqualTo($start)
            && $moment->lessThanOrEqualTo($end);

        return [
            'allowed' => $allowed,
            'timezone' => $timezone,
            'current_day' => $day,
            'current_time' => $moment->format('H:i'),
            'next_callable_at' => $allowed ? $moment : $this->nextCallableAt($normalized, $timezone, $moment),
            'schedule' => $normalized,
        ];
    }

    public function nextCallableAt(array|string|null $schedule, string $timezone, ?CarbonInterface $moment = null): ?CarbonImmutable
    {
        $normalized = $this->normalizeSchedule($schedule);
        $moment = $this->toImmutable($moment, $timezone);

        for ($offset = 0; $offset <= 7; $offset++) {
            $candidateDay = $moment->startOfDay()->addDays($offset);
            $day = strtolower($candidateDay->englishDayOfWeek);
            $window = $normalized[$day] ?? null;

            if (! $window || ! ($window['enabled'] ?? false)) {
                continue;
            }

            $start = $this->windowAt($candidateDay, $window['start']);
            $end = $this->windowAt($candidateDay, $window['end']);

            if (! $start || ! $end) {
                continue;
            }

            if ($offset === 0 && $moment->lessThanOrEqualTo($end)) {
                return $moment->lessThan($start) ? $start : $moment;
            }

            if ($offset > 0) {
                return $start;
            }
        }

        return null;
    }

    public function summarize(array|string|null $schedule): string
    {
        $normalized = $this->normalizeSchedule($schedule);

        return collect(self::DAYS)->map(function (string $day) use ($normalized) {
            $window = $normalized[$day];

            if (! ($window['enabled'] ?? false)) {
                return ucfirst(substr($day, 0, 3)) . ': off';
            }

            return ucfirst(substr($day, 0, 3)) . ': ' . $window['start'] . '-' . $window['end'];
        })->implode(' | ');
    }

    private function toImmutable(?CarbonInterface $moment, string $timezone): CarbonImmutable
    {
        if (! $moment) {
            return CarbonImmutable::now($timezone);
        }

        return CarbonImmutable::instance($moment)->setTimezone($timezone);
    }

    private function windowAt(CarbonImmutable $moment, ?string $time): ?CarbonImmutable
    {
        if (! $time) {
            return null;
        }

        [$hours, $minutes] = array_pad(explode(':', $time), 2, '00');

        return $moment->setTime((int) $hours, (int) $minutes);
    }
}
