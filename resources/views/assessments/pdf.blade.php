@php
    $houseLabels    = ['own' => 'ملك', 'rent' => 'إيجار', 'family' => 'عائلي', 'other' => 'أخرى'];
    $maritalLabels  = ['married' => 'متزوج', 'divorced' => 'مطلّق', 'widowed' => 'أرمل', 'abandoned' => 'تارك للبيت'];
    $genderLabels   = ['m' => 'ذكر', 'f' => 'أنثى'];
    $archLabels     = ['ممتاز', 'جيد', 'سيئ', 'لا يصلح'];
    $decisionLabels = ['pending' => 'قيد الانتظار', 'accepted' => 'مقبول', 'rejected' => 'مرفوض'];

    $fam      = $a->family;
    $incomes  = $a->finances->where('type', 'income');
    $expenses = $a->finances->where('type', 'expense');

    $money = fn ($v) => number_format((float) $v, 2) . ' ₪';

    $incomeTotal    = (float) $incomes->sum('amount');
    $expenseMonthly = (float) $expenses->reduce(fn ($c, $f) => $c + ($f->is_bimonthly ? $f->amount / 2 : $f->amount), 0);
    $familyRemaining = $incomeTotal - $expenseMonthly;

    $dash = fn ($v) => ($v === null || $v === '') ? '—' : $v;
@endphp
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<style>
    * { font-family: xbriyaz, sans-serif; }
    body { font-size: 11px; color: #1f2937; }
    .head { border-bottom: 2px solid #4f46e5; padding-bottom: 6px; margin-bottom: 10px; }
    .head .org { color: #4f46e5; font-size: 16px; font-weight: bold; }
    .head .sub { color: #6b7280; font-size: 10px; }
    .head .meta { color: #6b7280; font-size: 10px; margin-top: 2px; }

    table { width: 100%; border-collapse: collapse; }

    .summary td { text-align: center; border: 1px solid #e5e7eb; padding: 8px 4px; width: 25%; }
    .summary .val { font-size: 16px; font-weight: bold; color: #4f46e5; }
    .summary .lab { font-size: 9px; color: #6b7280; }
    .ok { color: #15803d; font-weight: bold; }
    .no { color: #6b7280; font-weight: bold; }

    .box { border: 1px solid #e5e7eb; margin-bottom: 9px; }
    .box h3 { background: #eef2ff; color: #3730a3; padding: 5px 9px; margin: 0; font-size: 12px; border-bottom: 1px solid #e5e7eb; }
    .box .bd { padding: 7px 9px; }

    .kv td { padding: 3px 4px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
    .kv td.k { color: #6b7280; width: 30%; }
    .col { width: 50%; vertical-align: top; padding: 0 4px; }
    .subhead { font-size: 10px; font-weight: bold; color: #9ca3af; margin-bottom: 2px; }

    .data th { background: #f9fafb; text-align: right; padding: 5px; font-size: 10px; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
    .data td { padding: 5px; border-bottom: 1px solid #f3f4f6; font-size: 10px; }
    .tag { background: #eef2ff; color: #3730a3; padding: 1px 5px; font-size: 9px; border-radius: 3px; }
    .right { text-align: left; }
    .total td { font-weight: bold; border-top: 1px solid #d1d5db; }
    .needs span { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; padding: 2px 7px; font-size: 10px; display: inline-block; margin: 2px; }
    .muted { color: #9ca3af; }
</style>
</head>
<body>

    <div class="head">
        <table>
            <tr>
                <td>
                    <div class="org">منظومة تقييم العائلات</div>
                    <div class="sub">مؤسسة عطاء — استمارة تقييم عائلة</div>
                </td>
                <td class="right" style="vertical-align: bottom;">
                    <div class="meta">رقم التقييم: #{{ $a->id }}</div>
                    <div class="meta">السياسة المعتمدة: v{{ $a->policy?->version }}</div>
                    <div class="meta">تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ملخّص النتيجة --}}
    <table class="summary">
        <tr>
            <td><div class="val">{{ $a->total_score }}</div><div class="lab">النقاط</div></td>
            <td><div class="val">{{ $money($a->per_person_remaining) }}</div><div class="lab">المتبقي للفرد</div></td>
            <td>
                <div class="val {{ $a->recommended ? 'ok' : 'no' }}" style="font-size:13px;">{{ $a->recommended ? 'مستحق' : 'غير مستحق' }}</div>
                <div class="lab">التوصية الآلية</div>
            </td>
            <td>
                <div class="val" style="font-size:13px; color:#111827;">{{ $decisionLabels[$a->decision] ?? $a->decision }}</div>
                <div class="lab">القرار اليدوي</div>
            </td>
        </tr>
    </table>
    <div style="height:9px;"></div>

    {{-- منفذو الزيارة --}}
    <div class="box">
        <h3>منفذو الزيارة</h3>
        <div class="bd">
            <table class="kv">
                <tr>
                    <td class="k">تاريخ الزيارة</td><td>{{ $dash($a->visit_date?->toDateString()) }}</td>
                    <td class="k">الزيارة التالية</td><td>{{ $dash($a->next_visit_date?->toDateString()) }}</td>
                </tr>
                <tr>
                    <td class="k">الزائرون</td><td colspan="3">{{ $dash($a->visitors) }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- بيانات العائلة --}}
    <div class="box">
        <h3>بيانات العائلة</h3>
        <div class="bd">
            <table>
                <tr>
                    <td class="col">
                        <div class="subhead">الزوج</div>
                        <table class="kv">
                            <tr><td class="k">الاسم</td><td>{{ $dash($fam->husband_name) }}</td></tr>
                            <tr><td class="k">الهوية</td><td>{{ $dash($fam->husband_id) }}</td></tr>
                            <tr><td class="k">الهاتف</td><td>{{ $dash($fam->husband_phone) }}</td></tr>
                            <tr><td class="k">تاريخ الميلاد</td><td>{{ $dash($fam->husband_dob?->toDateString()) }}</td></tr>
                        </table>
                    </td>
                    <td class="col">
                        <div class="subhead">الزوجة</div>
                        <table class="kv">
                            <tr><td class="k">الاسم</td><td>{{ $dash($fam->wife_name) }}</td></tr>
                            <tr><td class="k">الهوية</td><td>{{ $dash($fam->wife_id) }}</td></tr>
                            <tr><td class="k">الهاتف</td><td>{{ $dash($fam->wife_phone) }}</td></tr>
                            <tr><td class="k">تاريخ الميلاد</td><td>{{ $dash($fam->wife_dob?->toDateString()) }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table class="kv" style="margin-top:5px; border-top:1px solid #f3f4f6;">
                <tr>
                    <td class="k">الحالة الاجتماعية</td><td>{{ $maritalLabels[$fam->marital_status] ?? $fam->marital_status }}</td>
                    <td class="k">صندوق المرضى</td><td>{{ $dash($fam->health_fund) }}</td>
                </tr>
                <tr>
                    <td class="k">البنك</td><td>{{ $dash($fam->bank_name) }}</td>
                    <td class="k">حساب مشترك</td><td>{{ $fam->joint_account ? 'نعم' : 'لا' }}</td>
                </tr>
                <tr>
                    <td class="k">المسؤول عن العائلة</td><td>{{ $dash(optional($fam->supervisor)->name) }}</td>
                    <td class="k">وصف العائلة</td><td>{{ $dash($fam->description) }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- السكن والمنزل --}}
    <div class="box">
        <h3>بيانات السكن والمنزل</h3>
        <div class="bd">
            <table class="kv">
                <tr>
                    <td class="k">نوع السكن</td><td>{{ $houseLabels[$a->house_type] ?? $a->house_type }}</td>
                    <td class="k">الحالة المعمارية</td><td>{{ $archLabels[$a->arch_condition] ?? $a->arch_condition }}</td>
                </tr>
                <tr>
                    <td class="k">موقع المنزل</td><td>{{ $dash($a->house_location) }}</td>
                    <td class="k">يوجد أيتام</td><td>{{ $a->has_orphans ? 'نعم' : 'لا' }}</td>
                </tr>
                <tr>
                    <td class="k">يحتاج ترميم</td><td>{{ $a->needs_repair ? 'نعم' : 'لا' }}</td>
                    <td class="k">ملاحظات الترميم</td><td>{{ $dash($a->repairs_notes) }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- الأفراد --}}
    <div class="box">
        <h3>الأفراد / أفراد العائلة</h3>
        <div class="bd">
            @if ($a->members->isEmpty())
                <span class="muted">لا أفراد.</span>
            @else
                <table class="data">
                    <thead>
                        <tr>
                            <th>الاسم</th><th>الجنس</th><th>الميلاد (العمر)</th><th>المدرسة</th><th>سمات</th><th>الأهلية</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($a->members as $m)
                            @php
                                $traits = [];
                                if ($m->contributes) $traits[] = 'يعمل ويساهم';
                                if ($m->higher_education) $traits[] = 'طالب جامعي';
                                if ($m->needs_tutoring) $traits[] = 'دعم' . ($m->tutor_subject ? ': ' . $m->tutor_subject : '');
                                if ($m->is_orphan) $traits[] = 'يتيم';
                            @endphp
                            <tr>
                                <td>{{ $m->name ?: '—' }}</td>
                                <td>{{ $genderLabels[$m->gender] ?? '—' }}</td>
                                <td>{{ $m->dob ? $m->dob->toDateString() . ' (' . $m->dob->age . ')' : '—' }}</td>
                                <td>{{ $m->school ?: '—' }}</td>
                                <td>{{ count($traits) ? implode('، ', $traits) : '—' }}</td>
                                <td>{!! $m->is_eligible ? '<span class="ok">مستحق</span>' : '<span class="muted">غير مستحق</span>' !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- المالية --}}
    <div class="box">
        <h3>الوضع المالي</h3>
        <div class="bd">
            <table>
                <tr>
                    <td class="col">
                        <div class="subhead">المدخولات</div>
                        @if ($incomes->isEmpty())
                            <span class="muted">لا مدخولات.</span>
                        @else
                            <table class="data">
                                @foreach ($incomes as $f)
                                    <tr><td>{{ $f->category }}</td><td class="right">{{ $money($f->amount) }}</td></tr>
                                @endforeach
                                <tr class="total"><td>إجمالي المدخول</td><td class="right">{{ $money($incomeTotal) }}</td></tr>
                            </table>
                        @endif
                    </td>
                    <td class="col">
                        <div class="subhead">المصروفات</div>
                        @if ($expenses->isEmpty())
                            <span class="muted">لا مصروفات.</span>
                        @else
                            <table class="data">
                                @foreach ($expenses as $f)
                                    <tr>
                                        <td>{{ $f->category }}{!! $f->is_bimonthly ? ' <span class="tag">كل شهرين</span>' : '' !!}</td>
                                        <td class="right">{{ $money($f->amount) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="total"><td>المصروف الشهري</td><td class="right">{{ $money($expenseMonthly) }}</td></tr>
                            </table>
                        @endif
                    </td>
                </tr>
            </table>
            <table class="kv" style="margin-top:6px; border-top:1px solid #e5e7eb;">
                <tr>
                    <td class="k">المتبقي للعائلة (شهري)</td><td>{{ $money($familyRemaining) }}</td>
                    <td class="k">المتبقي للفرد</td><td><b>{{ $money($a->per_person_remaining) }}</b></td>
                </tr>
            </table>
        </div>
    </div>

    {{-- النواقص --}}
    <div class="box">
        <h3>النواقص الأساسية للمنزل</h3>
        <div class="bd needs">
            @if ($a->homeNeeds->isEmpty())
                <span class="muted">لا نواقص.</span>
            @else
                @foreach ($a->homeNeeds as $h)<span>{{ $h->item }}</span>@endforeach
            @endif
        </div>
    </div>

    {{-- القرار --}}
    <div class="box">
        <h3>القرار النهائي</h3>
        <div class="bd">
            <table class="kv">
                <tr><td class="k">القرار</td><td>{{ $decisionLabels[$a->decision] ?? $a->decision }}</td></tr>
                <tr><td class="k">ملاحظة القرار</td><td>{{ $dash($a->decision_note) }}</td></tr>
                <tr><td class="k">تاريخ القرار</td><td>{{ $dash(optional($a->decided_at)->format('Y-m-d H:i')) }}</td></tr>
            </table>
        </div>
    </div>

    {{-- ملاحظات العائلة --}}
    @if ($fam->notes->isNotEmpty())
        <div class="box">
            <h3>ملاحظات العائلة</h3>
            <div class="bd">
                <table class="data">
                    @foreach ($fam->notes as $note)
                        <tr>
                            <td style="width:120px; vertical-align:top;">
                                {{ optional($note->created_at)->format('Y-m-d H:i') }}
                                @if ($note->author)<br><span class="muted">{{ $note->author->name }}</span>@endif
                            </td>
                            <td style="vertical-align:top;">{{ $note->body }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endif

    {{-- سجل التدقيق --}}
    @if ($a->audits->isNotEmpty())
        <div class="box">
            <h3>سجل التدقيق</h3>
            <div class="bd">
                <table class="data">
                    @foreach ($a->audits as $au)
                        <tr>
                            <td style="width:90px;">{{ optional($au->created_at)->toDateString() }}</td>
                            <td>
                                @if ($au->action === 'decided')
                                    قرار: {{ $decisionLabels[$au->from_decision] ?? $au->from_decision }} ← {{ $decisionLabels[$au->to_decision] ?? $au->to_decision }}
                                @elseif ($au->action === 'converted_policy')
                                    تحويل سياسة: v{{ $au->from_version }} ← v{{ $au->to_version }} (نقاط {{ $au->from_score }} ← {{ $au->to_score }})
                                @else
                                    {{ $au->action }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endif

</body>
</html>
