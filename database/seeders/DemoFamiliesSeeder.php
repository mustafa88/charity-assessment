<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Family;
use App\Models\ScoringPolicy;
use App\Models\Supervisor;
use App\Services\ScoringEngine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * بيانات تجريبية (Demo) لعرض المنظومة: 24 عائلة متنوّعة مع أبناء ومالية ونواقص.
 *
 * يحاكي تماماً تدفّق AssessmentController@store:
 *   إنشاء العائلة ← التقييم ← الأفراد/المالية/النواقص ← قاعدة الأيتام ← إعادة الحساب بالمحرّك.
 *
 * التشغيل:  php artisan db:seed --class=DemoFamiliesSeeder
 * (يتطلّب سياسة نقاط معتمدة — تُنشأ تلقائياً إن غابت.)
 */
class DemoFamiliesSeeder extends Seeder
{
    public function run(): void
    {
        $engine = app(ScoringEngine::class);

        // سياسة معتمدة (تُنشأ إن غابت — نفس قيم v1 الافتراضية).
        $this->call(ScoringPolicySeeder::class);
        $policy = ScoringPolicy::where('is_active', true)->latest('version')->firstOrFail();

        // المسؤولون عن العائلات.
        $supervisors = collect([
            ['name' => 'أبو محمد القائد',   'phone' => '0599100001'],
            ['name' => 'سعاد الأحمد',        'phone' => '0599100002'],
            ['name' => 'خالد أبو ريّا',      'phone' => '0599100003'],
            ['name' => 'منى الشريف',         'phone' => '0599100004'],
        ])->map(fn ($s) => Supervisor::firstOrCreate(['name' => $s['name']], $s));

        // معطيات الأسماء.
        $husbandNames = ['محمد', 'أحمد', 'خالد', 'يوسف', 'إبراهيم', 'عمر', 'سامي', 'ماجد', 'رائد', 'نبيل', 'وليد', 'فادي'];
        $wifeNames    = ['فاطمة', 'مريم', 'سعاد', 'هدى', 'آمنة', 'ليلى', 'نور', 'رنا', 'سحر', 'وفاء', 'إيمان', 'عبير'];
        $surnames     = ['العمري', 'الشريف', 'أبو ريّا', 'الأحمد', 'الخطيب', 'حمدان', 'البرغوثي', 'النجار', 'دراغمة', 'عوض'];
        $boyNames     = ['عبدالله', 'كريم', 'زيد', 'آدم', 'سيف', 'حمزة', 'أنس', 'بلال', 'جواد', 'طارق'];
        $girlNames    = ['سلمى', 'جنى', 'لين', 'رغد', 'ميار', 'تالا', 'دانا', 'يارا', 'شهد', 'بتول'];
        $subjects     = ['رياضيات', 'لغة عربية', 'إنجليزي', 'علوم'];
        $healthFunds  = ['كلاليت', 'مكابي', 'لئوميت', 'مئوحيدت'];
        $banks        = ['بنك هبوعليم', 'بنك لئومي', 'بنك دسكونت', 'بنك البريد'];

        // أنماط الحالة الاجتماعية (تنويع: متزوج غالباً + أرامل/مطلقات/متروكات).
        $maritalCycle = [
            'married', 'married', 'widowed', 'married', 'divorced',
            'married', 'widowed', 'married', 'abandoned', 'married',
            'married', 'widowed', 'divorced', 'married', 'married',
            'widowed', 'married', 'abandoned', 'married', 'divorced',
            'married', 'widowed', 'married', 'married',
        ];
        $houseTypes = ['rent', 'own', 'rent', 'family', 'rent', 'own', 'rent', 'rent', 'family', 'own'];

        $idSeq    = 305_400_100;   // هويات فريدة متسلسلة
        $phoneSeq = 5_210_000;     // أرقام هواتف فريدة (تُسبق بـ 052)

        $total = count($maritalCycle); // 24

        DB::transaction(function () use (
            $engine, $policy, $supervisors, $husbandNames, $wifeNames, $surnames,
            $boyNames, $girlNames, $subjects, $healthFunds, $banks, $maritalCycle,
            $houseTypes, &$idSeq, &$phoneSeq, $total
        ) {
            for ($i = 0; $i < $total; $i++) {
                $marital  = $maritalCycle[$i];
                $surname  = $surnames[$i % count($surnames)];
                $hasHusband = ! in_array($marital, ['widowed'], true);            // الأرملة بلا زوج
                $hasWife    = ! in_array($marital, ['divorced', 'abandoned'], true) || $marital === 'abandoned';
                // في الطلاق نُبقي الزوجة كمعيلة؛ في الترك الزوجة موجودة والزوج غائب.
                $wifePresent    = $marital !== 'divorced' ? true : true;          // الزوجة دائماً معيلة هنا
                $husbandPresent = $marital === 'married';                          // الزوج حاضر فقط عند الزواج

                $husbandPhone = $husbandPresent ? '052' . str_pad((string) (++$phoneSeq), 7, '0', STR_PAD_LEFT) : null;
                $wifePhone    = '052' . str_pad((string) (++$phoneSeq), 7, '0', STR_PAD_LEFT);

                $family = Family::create([
                    'husband_name'   => $husbandPresent ? ($husbandNames[$i % count($husbandNames)] . ' ' . $surname) : null,
                    'wife_name'      => $wifeNames[$i % count($wifeNames)] . ' ' . $surname,
                    'husband_id'     => $husbandPresent ? (string) (++$idSeq) : null,
                    'wife_id'        => (string) (++$idSeq),
                    'husband_dob'    => $husbandPresent ? Carbon::now()->subYears(38 + ($i % 12))->toDateString() : null,
                    'wife_dob'       => Carbon::now()->subYears(34 + ($i % 10))->toDateString(),
                    'husband_phone'  => $husbandPhone,
                    'wife_phone'     => $wifePhone,
                    'marital_status' => $marital,
                    'health_fund'    => $healthFunds[$i % count($healthFunds)],
                    'bank_name'      => $banks[$i % count($banks)],
                    'joint_account'  => $i % 3 === 0,
                    'supervisor_id'  => $i % 6 === 5 ? null : $supervisors[$i % $supervisors->count()]->id, // بعضها بلا مسؤول
                    'description'    => 'عائلة تجريبية رقم ' . ($i + 1) . ' — لأغراض العرض.',
                ]);

                // --- التقييم ---
                $visitDate = Carbon::now()->subDays(($i * 11) % 200);
                $assessment = $family->assessments()->create([
                    'scoring_policy_id' => $policy->id,
                    'visit_date'        => $visitDate->toDateString(),
                    'visitors'          => $supervisors[$i % $supervisors->count()]->name,
                    'next_visit_date'   => $visitDate->copy()->addMonths(6)->toDateString(),
                    'house_type'        => $houseTypes[$i % count($houseTypes)],
                    'arch_condition'    => $i % 4, // 0..3
                    'needs_repair'      => $i % 4 >= 2,
                    'house_location'    => 'حي ' . $surname . ' — شارع ' . (($i % 20) + 1),
                    'repairs_notes'     => $i % 4 >= 2 ? 'يحتاج ترميم السقف وتمديدات الكهرباء.' : null,
                ]);

                // --- الأبناء (عدد متنوّع + أعمار متنوّعة) ---
                $childCount = 2 + ($i % 5); // 2..6
                for ($c = 0; $c < $childCount; $c++) {
                    $isBoy = ($i + $c) % 2 === 0;
                    // أعمار متدرّجة: بعضهم صغار، بعضهم مراهقون، وأحياناً 18+
                    $age = match (true) {
                        $c === 0 && $i % 3 === 0 => 19 + ($i % 4),   // أحياناً ابن/بنت 18+
                        default                  => 1 + (($i + $c * 3) % 16),
                    };
                    $contributes     = $age >= 18 && $isBoy && ($i % 2 === 0);     // ابن بالغ يعمل ويساهم
                    $higherEducation = $age >= 18 && ! $contributes && ($i % 2 === 0);
                    $needsTutoring   = $age >= 6 && $age <= 15 && ($c % 2 === 0);

                    $assessment->members()->create([
                        'name'             => ($isBoy ? $boyNames[($i + $c) % count($boyNames)] : $girlNames[($i + $c) % count($girlNames)]) . ' ' . $surname,
                        'dob'              => Carbon::now()->subYears($age)->subMonths(($c * 4) % 12)->toDateString(),
                        'gender'           => $isBoy ? 'm' : 'f',
                        'school'           => $age >= 6 && $age <= 17 ? 'مدرسة ' . $surname . ' الأساسية' : ($higherEducation ? 'جامعة' : null),
                        'needs_tutoring'   => $needsTutoring,
                        'tutor_subject'    => $needsTutoring ? $subjects[$c % count($subjects)] : null,
                        'higher_education' => $higherEducation,
                        'marital_status'   => null,
                        'contributes'      => $contributes,
                        'is_orphan'        => false, // تطبّقه قاعدة الأيتام بعد الحفظ
                        'is_eligible'      => false, // يحسبه المحرّك
                    ]);
                }

                // --- المالية (مدخولات ومصروفات واقعية، الباقي للفرد منخفض غالباً) ---
                $finances = [];

                // مدخولات
                if ($husbandPresent) {
                    $finances[] = ['type' => 'income', 'category' => 'عمل الأب', 'amount' => 2500 + ($i % 6) * 400, 'is_bimonthly' => false, 'notes' => 'عمل مياومة'];
                } else {
                    $finances[] = ['type' => 'income', 'category' => 'مخصصات التأمين الوطني', 'amount' => 1800 + ($i % 4) * 250, 'is_bimonthly' => false, 'notes' => null];
                }
                if ($i % 3 === 0) {
                    $finances[] = ['type' => 'income', 'category' => 'عمل الأم', 'amount' => 1200 + ($i % 3) * 300, 'is_bimonthly' => false, 'notes' => 'تنظيف بيوت'];
                }
                $finances[] = ['type' => 'income', 'category' => 'مساعدات جمعيات', 'amount' => 300 + ($i % 4) * 150, 'is_bimonthly' => true, 'notes' => 'كل شهرين'];

                // مصروفات
                if ($houseTypes[$i % count($houseTypes)] === 'rent') {
                    $finances[] = ['type' => 'expense', 'category' => 'إيجار', 'amount' => 1500 + ($i % 5) * 200, 'is_bimonthly' => false, 'notes' => null];
                }
                $finances[] = ['type' => 'expense', 'category' => 'كهرباء', 'amount' => 220 + ($i % 6) * 40, 'is_bimonthly' => true, 'notes' => 'فاتورة كل شهرين'];
                $finances[] = ['type' => 'expense', 'category' => 'ماء', 'amount' => 160 + ($i % 4) * 30, 'is_bimonthly' => true, 'notes' => null];
                $finances[] = ['type' => 'expense', 'category' => 'طعام وشراب', 'amount' => 1800 + $childCount * 250, 'is_bimonthly' => false, 'notes' => null];
                $finances[] = ['type' => 'expense', 'category' => 'مواصلات ومدارس', 'amount' => 300 + $childCount * 90, 'is_bimonthly' => false, 'notes' => null];
                if ($i % 2 === 0) {
                    $finances[] = ['type' => 'expense', 'category' => 'علاج وأدوية', 'amount' => 250 + ($i % 5) * 120, 'is_bimonthly' => false, 'notes' => 'مرض مزمن'];
                }

                foreach ($finances as $f) {
                    $assessment->finances()->create($f);
                }

                // --- النواقص الأساسية ---
                $allNeeds = ['ثلاجة', 'غسالة', 'غاز للطبخ', 'سرير أطفال', 'خزانة ملابس', 'سخّان مياه', 'مكيّف', 'طاولة طعام'];
                $needsCount = $i % 7; // 0..6 نواقص
                for ($n = 0; $n < $needsCount; $n++) {
                    $assessment->homeNeeds()->create(['item' => $allNeeds[($i + $n) % count($allNeeds)]]);
                }

                // --- قاعدة الأيتام ثم إعادة الحساب (نفس منطق الـ controller) ---
                $this->applyOrphanRules($assessment);
                $this->recompute($engine, $assessment, $policy);

                // --- القرار اليدوي (توزيع للعرض: مقبول/قيد الانتظار/مرفوض) ---
                $decision = match (true) {
                    $i % 9 === 4 || $i % 9 === 8 => 'rejected',
                    $i % 9 === 2 || $i % 9 === 6 => 'pending',
                    default                      => 'accepted',
                };
                if ($decision !== 'pending') {
                    $assessment->update([
                        'decision'      => $decision,
                        'decision_note' => $decision === 'accepted' ? 'الحالة مستحقّة بعد المعاينة.' : 'الدخل يتجاوز عتبة الاستحقاق.',
                        'decided_at'    => Carbon::now()->subDays(($i * 7) % 60),
                    ]);
                }

                // --- ملاحظة عائلة (سجل تراكمي) ---
                $family->notes()->create([
                    'body'    => 'تمت زيارة العائلة ومعاينة وضعها. ' . ($assessment->has_orphans ? 'يوجد أيتام في العائلة.' : 'لا يوجد أيتام.'),
                    'user_id' => null,
                ]);
            }
        });

        $this->command?->info('تم إنشاء ' . $total . ' عائلة تجريبية مع تقييماتها.');
    }

    /** قاعدة الأيتام: أرملة + عمر < 15 ⇒ يتيم. تُضيف فقط. (مطابقة للـ controller) */
    private function applyOrphanRules(Assessment $a): void
    {
        $a->loadMissing('family', 'members');
        $widowed = $a->family->marital_status === 'widowed';

        foreach ($a->members as $m) {
            if ($m->is_orphan) continue;
            $age = $m->dob ? $m->dob->age : null;
            if ($widowed && $age !== null && $age < 15) {
                $m->update(['is_orphan' => true]);
            }
        }

        $a->update(['has_orphans' => $a->members()->where('is_orphan', true)->exists()]);
    }

    /** إعادة الحساب بالمحرّك وكتابة النقاط/التوصية/أهلية كل فرد. (مطابقة للـ controller) */
    private function recompute(ScoringEngine $engine, Assessment $a, ScoringPolicy $p): void
    {
        $a->load(['family', 'members', 'finances', 'homeNeeds']);
        $res = $engine->evaluate($a, $p);

        foreach ($a->members as $m) {
            $m->update(['is_eligible' => $res['member_eligibility'][$m->id] ?? false]);
        }

        $a->update([
            'total_score'          => $res['score'],
            'per_person_remaining' => $res['per_person'],
            'recommended'          => $res['recommended'],
        ]);
    }
}
