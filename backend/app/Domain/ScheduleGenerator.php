<?php

namespace App\Domain;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class ScheduleGenerator
{
    public function generate(string $start, string $end, string $frequency, int $day = 1, bool $weekdaysOnly = false, array $excluded = []): array
    {
        $from = CarbonImmutable::parse($start);
        $to = CarbonImmutable::parse($end);
        $dates = [];
        if ($to->lt($from) || $from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['end_date' => 'Rentang maksimal satu tahun.']);
        }
        for ($date = $from; $date->lte($to); $date = $date->addDay()) {
            $include = match ($frequency) {
                'daily' => ! $weekdaysOnly || $date->isWeekday(),
                'weekly' => $date->dayOfWeekIso === $day,
                'monthly' => $date->day === min($day, $date->daysInMonth),
                'quarterly' => in_array($date->month, [3, 6, 9, 12], true) && $date->isLastOfMonth(),
                default => false,
            };
            if ($include && ! in_array($date->toDateString(), $excluded, true)) {
                $dates[] = $date->toDateString();
            }
        }

        return $dates;
    }
}
