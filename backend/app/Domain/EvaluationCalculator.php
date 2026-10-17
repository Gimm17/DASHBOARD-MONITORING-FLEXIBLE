<?php

namespace App\Domain;

class EvaluationCalculator
{
    public function calculate(?string $value, string $target, string $final, bool $due, bool $eligible = true): array
    {
        $ratio = static fn ($v, $t) => $v === null || bccomp($t, '0', 4) <= 0 ? null : (float) bcdiv(bcmul($v, '100', 8), $t, 8);

        return [
            'value' => $value, 'target' => $target, 'final_target' => $final,
            'attainment' => $ratio($value, $target), 'progress' => $ratio($value, $final),
            'status' => ! $eligible ? 'TIDAK_DINILAI' : (! $due ? 'BELUM_JATUH_TEMPO' : ($value === null ? 'BELUM_ADA_DATA' : (bccomp($value, $target, 4) >= 0 ? 'TERCAPAI' : 'DI_BAWAH_TARGET'))),
        ];
    }
}
