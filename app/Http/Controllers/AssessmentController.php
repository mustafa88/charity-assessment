<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentAudit;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\ScoringPolicy;
use App\Services\ScoringEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentController extends Controller
{
    public function __construct(private ScoringEngine $engine) {}

    /** قائمة التقييمات مقسّمة حسب القرار (مقبولة | قيد الانتظار | مرفوضة). */
    public function index(Request $r)
    {
        $status = $r->query('status', 'accepted');
        if (! in_array($status, ['accepted', 'pending', 'rejected'], true)) {
            $status = 'accepted';
        }

        $counts = [
            'accepted' => Assessment::where('decision', 'accepted')->count(),
            'pending'  => Assessment::where('decision', 'pending')->count(),
            'rejected' => Assessment::where('decision', 'rejected')->count(),
        ];

        $assessments = Assessment::with(['family.supervisor', 'policy'])
            ->where('decision', $status)
            ->latest()
            ->get();

        $active = ScoringPolicy::where('is_active', true)->latest('version')->first();

        return view('assessments.index', compact('assessments', 'active', 'status', 'counts'));
    }

    /**
     * الزيارات القريبة المستحقّة — أحدث تقييم لكل عائلة، مرتّبة بتاريخ الزيارة التالية.
     * تُعرض فقط العائلات **المقبولة بالقرار اليدوي** (decision = accepted).
     */
    public function upcoming()
    {
        $visits = Family::with('assessments')->get()
            ->map(function ($f) {
                $a = $f->assessments->first();          // الأحدث (العلاقة مرتّبة بـ visit_date تنازلياً)
                if ($a) { $a->setRelation('family', $f); }
                return $a;
            })
            ->filter(fn ($a) => $a && $a->next_visit_date && $a->decision === 'accepted')
            ->sortBy('next_visit_date')
            ->values();

        return view('visits.upcoming', compact('visits'));
    }

    /** مراجعة الأيتام: من بلغ 15 سنة فأكثر ولا يزال مُحدَّداً يتيماً (أحدث تقييم لكل عائلة). */
    public function orphanReviews()
    {
        $reviews = collect();
        foreach (Family::with('assessments.members')->get() as $f) {
            $a = $f->assessments->first();              // الأحدث
            if (! $a) continue;
            if ($a->decision !== 'accepted') continue;  // العائلات المقبولة فقط
            foreach ($a->members as $m) {
                if ($m->is_orphan && $m->dob && $m->dob->age >= 15) {
                    $reviews->push((object) ['family' => $f, 'assessment' => $a, 'member' => $m, 'age' => $m->dob->age]);
                }
            }
        }
        $reviews = $reviews->sortByDesc('age')->values();

        return view('orphans.index', compact('reviews'));
    }

    /** الموافقة اليدوية على إخراج فرد من الأيتام (بعد بلوغه 15). يُسجَّل كملاحظة عائلة. */
    public function removeOrphan(Request $r, FamilyMember $member)
    {
        $member->loadMissing('assessment.family');
        $age = $member->dob ? $member->dob->age : null;

        if (! $member->is_orphan) {
            return back()->with('status', 'هذا الفرد ليس ضمن الأيتام.');
        }
        if ($age === null || $age < 15) {
            return back()->with('status', 'لا يمكن الإخراج: لم يبلغ 15 سنة بعد.');
        }

        DB::transaction(function () use ($r, $member, $age) {
            $member->update(['is_orphan' => false]);

            if ($assessment = $member->assessment) {
                $assessment->update(['has_orphans' => $assessment->members()->where('is_orphan', true)->exists()]);

                if ($family = $assessment->family) {
                    $family->notes()->create([
                        'body'    => "تمت الموافقة على إخراج «" . ($member->name ?: 'فرد') . "» من الأيتام (بلغ {$age} سنة).",
                        'user_id' => $r->user()?->id,
                    ]);
                }
            }
        });

        return back()->with('status', 'تمت الموافقة وإخراج الفرد من الأيتام.');
    }

    /** نموذج تقييم جديد. */
    public function create()
    {
        $policy = ScoringPolicy::where('is_active', true)->latest('version')->first();
        if (! $policy) {
            return redirect()->route('policies.index')
                ->with('status', 'لا توجد سياسة نقاط معتمدة بعد. أنشئ السياسة الأولى قبل إضافة أي تقييم.');
        }
        $supervisors = \App\Models\Supervisor::orderBy('name')->get();

        return view('assessments.create', compact('policy', 'supervisors'));
    }

    /** تفاصيل تقييم. */
    public function show(Assessment $a)
    {
        $a->load(['family.notes.author', 'family.attachments.author', 'family.supervisor', 'members', 'finances', 'homeNeeds', 'policy', 'audits']);
        $active = ScoringPolicy::where('is_active', true)->latest('version')->first();

        return view('assessments.show', ['a' => $a, 'active' => $active]);
    }

    /** تصدير التقييم كملف PDF عربي مرتّب (mPDF). */
    public function pdf(Assessment $a)
    {
        $a->load(['family.supervisor', 'family.notes.author', 'members', 'finances', 'homeNeeds', 'policy', 'audits']);

        $html = view('assessments.pdf', ['a' => $a])->render();

        return $this->streamPdf($html, 'assessment-' . $a->id . '.pdf');
    }

    /**
     * يرندر HTML إلى ملف PDF عربي (mPDF) ويعيده inline.
     * $format: 'A4' (طولي) أو 'A4-L' (عرضي للجداول العريضة).
     */
    private function streamPdf(string $html, string $filename, string $format = 'A4')
    {
        $tmp = storage_path('app/mpdf');
        if (! is_dir($tmp)) { mkdir($tmp, 0775, true); }

        $mpdf = new \Mpdf\Mpdf([
            'mode'           => 'utf-8',
            'format'         => $format,
            'default_font'   => 'xbriyaz',
            'tempDir'        => $tmp,
            'margin_top'     => 12,
            'margin_bottom'  => 14,
            'margin_left'    => 12,
            'margin_right'   => 12,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont   = true;
        $mpdf->SetFooter('||صفحة {PAGENO} من {nbpg}');
        $mpdf->WriteHTML($html);

        return response(
            $mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]
        );
    }

    /** قائمة جميع الأيتام ضمن العائلات المقبولة (صفحة عرض + تصدير). */
    public function orphans()
    {
        $orphans = $this->collectAcceptedOrphans();

        return view('orphans.all', compact('orphans'));
    }

    /** تصدير قائمة الأيتام كملف PDF عربي (عرضي). */
    public function orphansPdf()
    {
        $orphans = $this->collectAcceptedOrphans();

        $html = view('orphans.pdf', compact('orphans'))->render();

        return $this->streamPdf($html, 'orphans.pdf', 'A4-L');
    }

    /**
     * يجمع كل الأفراد المُحدَّدين «يتيم» ضمن العائلات المقبولة (أحدث تقييم قراره accepted).
     * يعيد صفوفاً جاهزة للعرض: الاسم، العمر، الجنس، اسم الأم (الزوجة)، الهاتف، المسؤول، العائلة.
     */
    private function collectAcceptedOrphans()
    {
        $rows = collect();
        foreach (Family::with(['assessments.members', 'supervisor'])->get() as $f) {
            $a = $f->assessments->first();              // الأحدث
            if (! $a || $a->decision !== 'accepted') continue;

            foreach ($a->members as $m) {
                if (! $m->is_orphan) continue;
                $rows->push((object) [
                    'name'       => $m->name,
                    'age'        => $m->dob ? $m->dob->age : null,
                    'gender'     => $m->gender,
                    'mother'     => $f->wife_name,
                    'phone'      => $f->wife_phone ?: $f->husband_phone,
                    'supervisor' => $f->supervisor?->name,
                    'family'     => $f->husband_name ?: ($f->wife_name ?: ('عائلة #' . $f->id)),
                    'assessment' => $a,
                ]);
            }
        }

        return $rows->sortBy([['supervisor', 'asc'], ['family', 'asc']])->values();
    }

    /** نموذج تعديل تقييم. */
    public function edit(Assessment $a)
    {
        $a->load(['family', 'members', 'finances', 'homeNeeds']);
        $policy      = ScoringPolicy::active();
        $supervisors = \App\Models\Supervisor::orderBy('name')->get();

        return view('assessments.edit', ['a' => $a, 'policy' => $policy, 'supervisors' => $supervisors]);
    }

    /** إنشاء تقييم جديد بالسياسة الحالية المعتمدة. */
    public function store(Request $r)
    {
        if (! ScoringPolicy::where('is_active', true)->exists()) {
            return redirect()->route('policies.index')
                ->with('status', 'لا توجد سياسة نقاط معتمدة بعد. أنشئ السياسة الأولى أولاً.');
        }

        $data = $this->validateData($r);
        $this->ensureIdentifiersUnique($data['family']);

        $assessment = DB::transaction(function () use ($data) {
            $policy = ScoringPolicy::active();

            $family = Family::create($data['family']);
            $assessment = $family->assessments()->create(
                array_merge($data['assessment'], ['scoring_policy_id' => $policy->id])
            );

            $this->syncChildren($assessment, $data);
            $this->applyOrphanRules($assessment);
            $this->recompute($assessment, $policy);

            return $assessment;
        });

        return redirect()->route('assessments.show', $assessment)->with('status', 'تم إنشاء التقييم.');
    }

    public function update(Request $r, Assessment $a)
    {
        $data = $this->validateData($r);
        $this->ensureIdentifiersUnique($data['family'], $a->family_id);

        DB::transaction(function () use ($a, $data, $r) {
            // تواريخ الزيارة قبل التعديل — لتسجيل الزيارة السابقة في سجل التدقيق إن تغيّرت.
            $oldVisit     = optional($a->visit_date)->toDateString();
            $oldNextVisit = optional($a->next_visit_date)->toDateString();

            $a->family->update($data['family']);
            $a->update($data['assessment']);

            $a->members()->delete();
            $a->finances()->delete();
            $a->homeNeeds()->delete();
            $this->syncChildren($a, $data);
            $this->applyOrphanRules($a);

            $this->recompute($a, $a->policy);   // يُعاد الحساب بسياسة هذا التقييم نفسها

            // إذا تغيّر تاريخ الزيارة أو الزيارة التالية → نسجّل القديم في سجل التدقيق (زيارة جديدة على نفس التقييم).
            $newVisit     = optional($a->visit_date)->toDateString();
            $newNextVisit = optional($a->next_visit_date)->toDateString();
            if ($oldVisit !== $newVisit || $oldNextVisit !== $newNextVisit) {
                AssessmentAudit::create([
                    'assessment_id' => $a->id,
                    'action'        => 'revisited',
                    'user_id'       => $r->user()?->id,
                    'meta'          => [
                        'from_visit_date'      => $oldVisit,
                        'to_visit_date'        => $newVisit,
                        'from_next_visit_date' => $oldNextVisit,
                        'to_next_visit_date'   => $newNextVisit,
                    ],
                ]);
            }
        });

        return redirect()->route('assessments.show', $a)->with('status', 'تم تحديث التقييم.');
    }

    /** القرار النهائي اليدوي — لا علاقة له بالتوصية الآلية. */
    public function decide(Request $r, Assessment $a)
    {
        $r->validate([
            'decision' => 'required|in:pending,accepted,rejected',
            'note'     => 'nullable|string',
        ]);

        AssessmentAudit::create([
            'assessment_id' => $a->id,
            'action'        => 'decided',
            'from_decision' => $a->decision,
            'to_decision'   => $r->decision,
            'user_id'       => $r->user()?->id,
        ]);

        $a->update([
            'decision'      => $r->decision,
            'decision_note' => $r->note,
            'decided_at'    => now(),
        ]);

        return redirect()->route('assessments.show', $a)->with('status', 'تم تسجيل القرار.');
    }

    /** تحويل الحساب إلى الإصدار الحالي — قرار واعٍ للمستخدم. القرار اليدوي لا يُلمَس. */
    public function convertToLatest(Assessment $a)
    {
        $latest = ScoringPolicy::active();
        if ($a->scoring_policy_id === $latest->id) {
            return redirect()->route('assessments.show', $a)->with('status', 'هذا التقييم محسوب بالفعل بالإصدار الأحدث.');
        }

        DB::transaction(function () use ($a, $latest) {
            $oldVersion = $a->policy->version;
            $oldScore   = $a->total_score;

            $a->update(['scoring_policy_id' => $latest->id]);
            $a->load(['members', 'finances', 'homeNeeds']);
            $this->recompute($a, $latest);

            AssessmentAudit::create([
                'assessment_id' => $a->id,
                'action'        => 'converted_policy',
                'from_version'  => $oldVersion,
                'to_version'    => $latest->version,
                'from_score'    => $oldScore,
                'to_score'      => $a->total_score,
            ]);
        });

        return redirect()->route('assessments.show', $a)->with('status', 'تم التحويل للإصدار الأحدث وإعادة الحساب.');
    }

    /* ---------- helpers ---------- */

    private function recompute(Assessment $a, ScoringPolicy $p): void
    {
        $a->load(['family', 'members', 'finances', 'homeNeeds']);
        $res = $this->engine->evaluate($a, $p);

        foreach ($a->members as $m) {
            $m->update(['is_eligible' => $res['member_eligibility'][$m->id] ?? false]);
        }

        $a->update([
            'total_score'          => $res['score'],
            'per_person_remaining' => $res['per_person'],
            'recommended'          => $res['recommended'],
        ]);
    }

    /**
     * قاعدة الأيتام: تُضيف فقط ولا تُزيل.
     * يُضبط الفرد يتيماً آلياً إذا كانت الحالة الاجتماعية للعائلة "أرمل" وعمره < 15.
     * ثم يُشتق has_orphans للتقييم من وجود أي فرد يتيم. (الإزالة يدوية عبر صفحة المراجعة.)
     */
    private function applyOrphanRules(Assessment $a): void
    {
        $a->loadMissing('family', 'members');
        $widowed = $a->family->marital_status === 'widowed';

        foreach ($a->members as $m) {
            if ($m->is_orphan) continue;                     // لا نلمس من هو يتيم أصلاً
            $age = $m->dob ? $m->dob->age : null;
            if ($widowed && $age !== null && $age < 15) {
                $m->update(['is_orphan' => true]);
            }
        }

        $a->update(['has_orphans' => $a->members()->where('is_orphan', true)->exists()]);
    }

    private function syncChildren(Assessment $a, array $data): void
    {
        foreach ($data['members'] as $m) { $a->members()->create($m); }
        foreach ($data['finances'] as $f) {
            $f['amount'] = $f['amount'] ?? 0;   // فراغ المبلغ → 0
            $a->finances()->create($f);
        }
        foreach ($data['home_needs'] as $item) {
            if (filled($item)) { $a->homeNeeds()->create(['item' => $item]); }
        }
    }

    /**
     * يمنع تكرار الهويات والهواتف بين العائلات.
     * كل قيمة تُفحص في عمودَي الزوج والزوجة معاً (الهوية هوية بغضّ النظر عن الخانة).
     */
    private function ensureIdentifiersUnique(array $family, ?int $ignoreFamilyId = null): void
    {
        $checks = [
            'husband_id'    => ['label' => 'هوية الزوج',  'cols' => ['husband_id', 'wife_id']],
            'wife_id'       => ['label' => 'هوية الزوجة', 'cols' => ['husband_id', 'wife_id']],
            'husband_phone' => ['label' => 'هاتف الزوج',  'cols' => ['husband_phone', 'wife_phone']],
            'wife_phone'    => ['label' => 'هاتف الزوجة', 'cols' => ['husband_phone', 'wife_phone']],
        ];

        $errors = [];
        foreach ($checks as $field => $c) {
            $val = trim((string) ($family[$field] ?? ''));
            if ($val === '') continue;

            $owner = Family::when($ignoreFamilyId, fn ($q) => $q->whereKeyNot($ignoreFamilyId))
                ->where(function ($q) use ($c, $val) {
                    foreach ($c['cols'] as $col) { $q->orWhere($col, $val); }
                })
                ->first();

            if ($owner) {
                $errors["family.$field"][] = "{$c['label']} ($val) مُسجَّل مسبقاً لدى: " . $this->familyLabel($owner) . '.';
            }
        }

        // داخل نفس النموذج: هوية الزوج ≠ هوية الزوجة
        $hid = trim((string) ($family['husband_id'] ?? ''));
        $wid = trim((string) ($family['wife_id'] ?? ''));
        if ($hid !== '' && $hid === $wid) {
            $errors['family.wife_id'][] = 'هوية الزوجة مطابقة لهوية الزوج في نفس النموذج.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function familyLabel(Family $f): string
    {
        return $f->husband_name ?: ($f->wife_name ?: ('عائلة #' . $f->id));
    }

    /** رسائل تحقّق عربية واضحة. */
    private function validationMessages(): array
    {
        return [
            'family.marital_status.required'   => 'الحالة الاجتماعية للعائلة مطلوبة.',
            'assessment.visit_date.required'   => 'تاريخ الزيارة مطلوب.',
            'assessment.visit_date.date'       => 'تاريخ الزيارة غير صحيح.',
            'assessment.visitors.required'     => 'اسم منفّذ الزيارة مطلوب.',
            'assessment.house_type.required'   => 'نوع السكن مطلوب.',
            'assessment.arch_condition.required' => 'الحالة المعمارية مطلوبة.',

            'members.*.name.required'   => 'اسم الفرد مطلوب لكل فرد مُضاف.',
            'members.*.dob.required'    => 'تاريخ ميلاد الفرد مطلوب لكل فرد مُضاف.',
            'members.*.dob.date'        => 'تاريخ ميلاد الفرد غير صحيح.',
            'members.*.gender.required' => 'نوع الفرد (ذكر/أنثى) مطلوب لكل فرد مُضاف.',

            'finances.*.category.required' => 'وصف البند المالي مطلوب لكل بند مُضاف.',
            'finances.*.amount.required'   => 'مبلغ البند المالي مطلوب لكل بند مُضاف.',
            'finances.*.amount.numeric'    => 'المبلغ يجب أن يكون رقماً.',
            'finances.*.amount.min'        => 'المبلغ لا يمكن أن يكون سالباً.',
            'finances.*.type.required'     => 'نوع البند المالي مطلوب.',
        ];
    }

    /**
     * يجب أن تكتمل بيانات أحد الزوجين على الأقل:
     * الاسم + الهوية + الهاتف + تاريخ الميلاد لنفس الطرف.
     */
    private function ensureParentComplete(array $family): void
    {
        $complete = fn (string $side) => filled($family["{$side}_name"] ?? null)
            && filled($family["{$side}_id"] ?? null)
            && filled($family["{$side}_phone"] ?? null)
            && filled($family["{$side}_dob"] ?? null);

        if (! $complete('husband') && ! $complete('wife')) {
            throw ValidationException::withMessages([
                'family.husband_name' => 'يجب إدخال بيانات أحد الزوجين كاملة على الأقل: الاسم، الهوية، الهاتف، وتاريخ الميلاد.',
            ]);
        }
    }

    private function validateData(Request $r): array
    {
        $v = $r->validate([
            'family'                  => 'required|array',
            'family.wife_name'        => 'nullable|string',
            'family.husband_name'     => 'nullable|string',
            'family.wife_id'          => 'nullable|string',
            'family.husband_id'       => 'nullable|string',
            'family.wife_dob'         => 'nullable|date',
            'family.husband_dob'      => 'nullable|date',
            'family.marital_status'   => 'required|in:married,divorced,widowed,abandoned',
            'family.wife_phone'       => 'nullable|string',
            'family.husband_phone'    => 'nullable|string',
            'family.health_fund'      => 'nullable|string',
            'family.bank_name'        => 'nullable|string',
            'family.joint_account'    => 'boolean',
            'family.supervisor_id'    => 'nullable|integer|exists:supervisors,id',
            'family.description'      => 'nullable|string',

            'assessment'                => 'required|array',
            'assessment.visit_date'      => 'required|date',
            'assessment.visitors'        => 'required|string',
            'assessment.next_visit_date' => 'nullable|date',
            'assessment.house_type'     => 'required|in:own,rent,family,other',
            'assessment.arch_condition' => 'required|integer|between:0,3',
            'assessment.needs_repair'   => 'boolean',
            'assessment.house_location' => 'nullable|string',
            'assessment.repairs_notes'  => 'nullable|string',

            'members'                  => 'array',
            'members.*.name'           => 'required|string',
            'members.*.dob'            => 'required|date',
            'members.*.gender'         => 'required|in:m,f',
            'members.*.school'         => 'nullable|string',
            'members.*.needs_tutoring' => 'boolean',
            'members.*.tutor_subject'  => 'nullable|string',
            'members.*.higher_education' => 'boolean',
            'members.*.marital_status' => 'nullable|string',
            'members.*.contributes'    => 'boolean',
            'members.*.is_orphan'      => 'boolean',

            'finances'                => 'array',
            'finances.*.type'         => 'required|in:expense,income',
            'finances.*.category'     => 'required|string',
            'finances.*.amount'       => 'required|numeric|min:0',
            'finances.*.is_bimonthly' => 'boolean',
            'finances.*.notes'        => 'nullable|string',

            'home_needs'              => 'array',
            'home_needs.*'            => 'nullable|string',
        ], $this->validationMessages());

        // قاعدة العائلة: يجب اكتمال بيانات أحد الزوجين على الأقل (اسم + هوية + هاتف + ميلاد).
        $this->ensureParentComplete($v['family']);

        // الزيارة التالية افتراضياً = تاريخ الزيارة + 6 أشهر (إن تُركت فارغة).
        $assessment = $v['assessment'];
        if (empty($assessment['next_visit_date']) && ! empty($assessment['visit_date'])) {
            $assessment['next_visit_date'] = Carbon::parse($assessment['visit_date'])->addMonths(6)->toDateString();
        }

        // تطبيع الفراغات إلى null حتى لا تتعارض الحقول الفارغة مع فهارس الـ uniqueness.
        $family = $v['family'];
        foreach ($family as $k => $val) {
            if (is_string($val) && trim($val) === '') { $family[$k] = null; }
        }

        return [
            'family'     => $family,
            'assessment' => $assessment,
            'members'    => $v['members']    ?? [],
            'finances'   => $v['finances']   ?? [],
            'home_needs' => $v['home_needs'] ?? [],
        ];
    }
}
