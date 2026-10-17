<?php

namespace Tests\Unit;

use App\Domain\ScheduleGenerator;
use PHPUnit\Framework\TestCase;

class ScheduleTest extends TestCase
{
    public function test_month_end_and_leap_year(): void
    {
        $this->assertTrue(class_exists(ScheduleGenerator::class), 'Schedule generator is required');
        $generator = new ScheduleGenerator;
        $this->assertSame(['2027-01-31', '2027-02-28', '2027-03-31'], $generator->generate('2027-01-01', '2027-03-31', 'monthly', 31));
        $this->assertSame(['2028-02-29'], $generator->generate('2028-02-01', '2028-02-29', 'monthly', 31));
    }
}
