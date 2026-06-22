<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\ScoringPolicy;

/**
 * المصدر الوحيد لحساب النقاط والتوصية.
 * يستقبل السياسة كوسيط، فيعمل على السياسة الحالية أو أي إصدار مؤرشَف بنفس المنطق.
 *
 * ملاحظة: لا يتخذ القرار النهائي إطلاقاً — فقط يحسب النقاط ويُنتج "توصية".
 * القرار (accepted/rejected) يضعه المستخدم يدوياً.
 */
class ScoringEngine
{
    /**
     * @return array{
     *   income:float, expense:float, family_remaining:float, eligible_count:int,
     *   per_person:float, score:int, recommended:bool,
     *   breakdown:array<int,array{label:string,points:int}>,
     *   member_eligibility:array<int,bool>
     * }
     */
    public function evaluate(Assessment $a, ScoringPolicy $p): array
    {
        // --- 1) المالية ---
        $expense = 0.0;
        $income  = 0.0;
        foreach ($a->finances as $f) {
            $amount = (float) $f->amount;
            if ($f->type === 'expense') {
                $expense += $f->is_bimonthly ? $amount / 2 : $amount; // للشهرين -> شهري
            } else {
                $income += $amount;
            }
        }
        $familyRemaining = $income - $expense;

        // --- 2) الأفراد المستحقون ---
        [$eligibleCount, $memberEligibility] = $this->eligibleCount($a);
        $perPerson = $familyRemaining / max($eligibleCount, 1);

        // --- 3) النقاط (كلها من السياسة) ---
        $breakdown = [];
        $score = 0;

        if ($a->house_type === 'rent' && $p->rent_bonus) {
            $score += $p->rent_bonus;
            $breakdown[] = ['label' => 'نوع السكن (إيجار)', 'points' => $p->rent_bonus];
        }

        if (in_array($a->family->marital_status, ['divorced','widowed','abandoned'], true) && $p->marital_bonus) {
            $score += $p->marital_bonus;
            $breakdown[] = ['label' => 'الحالة الاجتماعية', 'points' => $p->marital_bonus];
        }

        $personsPts = $eligibleCount * $p->per_eligible_person;
        $score += $personsPts;
        $breakdown[] = ['label' => "الأفراد المستحقون ($eligibleCount)", 'points' => $personsPts];

        $perPts = $this->bandPoints($perPerson, $p->bands);
        $score += $perPts;
        $breakdown[] = ['label' => 'المتبقي للفرد (' . round($perPerson, 2) . '₪)', 'points' => $perPts];

        $missingCount = $a->homeNeeds->count();
        $missingPts = intdiv($missingCount, max($p->missing_group_size, 1)) * $p->missing_group_points;
        if ($missingPts) {
            $score += $missingPts;
            $breakdown[] = ['label' => "النواقص الأساسية ($missingCount)", 'points' => $missingPts];
        }

        $archPts = $p->arch_points[$a->arch_condition] ?? 0;
        if ($archPts) {
            $score += $archPts;
            $breakdown[] = ['label' => 'الحالة المعمارية', 'points' => $archPts];
        }

        // --- 4) التوصية (مساعِدة فقط) ---
        $recommended = $perPerson <= (float) $p->approval_threshold;

        return [
            'income'             => $income,
            'expense'            => $expense,
            'family_remaining'   => $familyRemaining,
            'eligible_count'     => $eligibleCount,
            'per_person'         => $perPerson,
            'score'              => $score,
            'recommended'        => $recommended,
            'breakdown'          => $breakdown,
            'member_eligibility' => $memberEligibility,
        ];
    }

    /** الأبوان + الأبناء المؤهلون. يعيد [العدد، خريطة أهلية كل عضو حسب id]. */
    private function eligibleCount(Assessment $a): array
    {
        // عدد الأهل الحاضرين: زواج = 2، غير ذلك (طلاق/وفاة/تارك) = 1
        $count = $a->family->marital_status === 'married' ? 2 : 1;

        $map = [];
        foreach ($a->members as $m) {
            $age = $m->dob ? $m->dob->age : null;
            $eligible = false;
            if ($age !== null) {
                if ($age < 18)                                $eligible = true; // تحت 18
                elseif ($m->gender === 'f' && ! $m->contributes) $eligible = true; // بنت 18+ غير عاملة
                elseif ($m->contributes)                      $eligible = true; // يعمل ويساهم
                elseif ($m->higher_education)                 $eligible = true; // طالب جامعي
            }
            $map[$m->id] = $eligible;
            if ($eligible) $count++;
        }

        return [max($count, 1), $map];
    }

    private function bandPoints(float $perPerson, array $bands): int
    {
        $fallback = (int) end($bands)['points'];
        foreach ($bands as $b) {
            if ($b['max'] !== null && $perPerson < (float) $b['max']) {
                return (int) $b['points'];
            }
        }
        return $fallback;
    }
}
