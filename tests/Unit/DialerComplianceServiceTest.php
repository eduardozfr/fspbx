<?php

namespace Tests\Unit;

use Carbon\CarbonImmutable;
use Modules\Dialer\Services\DialerComplianceService;
use PHPUnit\Framework\TestCase;

class DialerComplianceServiceTest extends TestCase
{
    public function test_it_allows_calls_inside_the_local_window(): void
    {
        $service = new DialerComplianceService();

        $result = $service->evaluate(
            [
                'monday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
            ],
            'America/New_York',
            CarbonImmutable::parse('2026-03-16 10:15:00', 'America/New_York')
        );

        $this->assertTrue($result['allowed']);
    }

    public function test_it_returns_the_next_callable_window_when_outside_hours(): void
    {
        $service = new DialerComplianceService();

        $result = $service->evaluate(
            [
                'monday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
                'tuesday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
            ],
            'America/New_York',
            CarbonImmutable::parse('2026-03-16 22:00:00', 'America/New_York')
        );

        $this->assertFalse($result['allowed']);
        $this->assertSame('2026-03-17 08:00', $result['next_callable_at']?->format('Y-m-d H:i'));
    }

    public function test_it_skips_disabled_days(): void
    {
        $service = new DialerComplianceService();

        $result = $service->evaluate(
            [
                'sunday' => ['enabled' => false, 'start' => null, 'end' => null],
                'monday' => ['enabled' => true, 'start' => '08:00', 'end' => '20:00'],
            ],
            'America/Chicago',
            CarbonImmutable::parse('2026-03-15 12:00:00', 'America/Chicago')
        );

        $this->assertFalse($result['allowed']);
        $this->assertSame('2026-03-16 08:00', $result['next_callable_at']?->format('Y-m-d H:i'));
    }
}
