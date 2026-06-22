{{--
    نموذج التقييم المشترك (إنشاء/تعديل).
    - الإرسال = POST/PUT عادي للسيرفر (لا fetch). السيرفر هو مصدر النقاط المعتمد.
    - Alpine يُستخدم فقط للصفوف الديناميكية والمعاينة الحيّة التقديرية.
    المتغيّرات الواردة: $a (التقييم عند التعديل، أو null)، $policy (السياسة المعتمدة للمعاينة).
--}}
@php
    $editing = isset($a) && $a;

    // بناء الحالة الابتدائية مع إعادة ملء old() عند فشل التحقق، وتطبيع القيم المنطقية لأنواع حقيقية.
    $b = fn ($v) => (bool) $v;

    $famModel = $editing ? [
        'husband_name' => $a->family->husband_name, 'wife_name' => $a->family->wife_name,
        'husband_id' => $a->family->husband_id, 'wife_id' => $a->family->wife_id,
        'husband_phone' => $a->family->husband_phone, 'wife_phone' => $a->family->wife_phone,
        'husband_dob' => optional($a->family->husband_dob)->toDateString(),
        'wife_dob' => optional($a->family->wife_dob)->toDateString(),
        'marital_status' => $a->family->marital_status,
        'health_fund' => $a->family->health_fund, 'bank_name' => $a->family->bank_name,
        'joint_account' => $a->family->joint_account,
        'supervisor_id' => $a->family->supervisor_id, 'description' => $a->family->description,
    ] : [
        'husband_name' => '', 'wife_name' => '', 'husband_id' => '', 'wife_id' => '',
        'husband_phone' => '', 'wife_phone' => '', 'husband_dob' => '', 'wife_dob' => '',
        'marital_status' => 'married',
        'health_fund' => '', 'bank_name' => '', 'joint_account' => false,
        'supervisor_id' => '', 'description' => '',
    ];
    $fam = old('family', $famModel);

    $assModel = $editing ? [
        'visit_date' => optional($a->visit_date)->toDateString(), 'visitors' => $a->visitors,
        'next_visit_date' => optional($a->next_visit_date)->toDateString(),
        'house_type' => $a->house_type,
        'arch_condition' => $a->arch_condition, 'has_orphans' => $a->has_orphans,
        'needs_repair' => $a->needs_repair, 'house_location' => $a->house_location,
        'repairs_notes' => $a->repairs_notes,
    ] : [
        'visit_date' => '', 'visitors' => '', 'next_visit_date' => '', 'house_type' => 'own',
        'arch_condition' => 0, 'has_orphans' => false, 'needs_repair' => false,
        'house_location' => '', 'repairs_notes' => '',
    ];
    $ass = old('assessment', $assModel);

    $membersModel = $editing ? $a->members->map(fn ($m) => [
        'name' => $m->name, 'dob' => optional($m->dob)->toDateString(), 'gender' => $m->gender ?: 'm',
        'school' => $m->school, 'needs_tutoring' => $m->needs_tutoring, 'tutor_subject' => $m->tutor_subject,
        'higher_education' => $m->higher_education, 'marital_status' => $m->marital_status, 'contributes' => $m->contributes,
        'is_orphan' => $m->is_orphan,
    ])->all() : [];
    $members = old('members', $membersModel);

    $financesModel = $editing ? $a->finances->map(fn ($f) => [
        'type' => $f->type, 'category' => $f->category, 'amount' => $f->amount,
        'is_bimonthly' => $f->is_bimonthly, 'notes' => $f->notes,
    ])->all() : [];
    $finances = old('finances', $financesModel);

    $needsModel = $editing ? $a->homeNeeds->pluck('item')->all() : [];
    $homeNeeds = old('home_needs', $needsModel);

    // تطبيع القيم المنطقية إلى true/false حقيقية (Alpine checkbox يعتبر السلسلة "0" قيمة صادقة!)
    $fam['joint_account'] = $b($fam['joint_account'] ?? false);
    $ass['has_orphans']   = $b($ass['has_orphans'] ?? false);
    $ass['needs_repair']  = $b($ass['needs_repair'] ?? false);
    $ass['arch_condition'] = (int) ($ass['arch_condition'] ?? 0);
    foreach ($members as &$m) {
        $m['needs_tutoring']   = $b($m['needs_tutoring'] ?? false);
        $m['higher_education'] = $b($m['higher_education'] ?? false);
        $m['contributes']      = $b($m['contributes'] ?? false);
        $m['is_orphan']        = $b($m['is_orphan'] ?? false);
    }
    unset($m);
    foreach ($finances as &$f) {
        $f['is_bimonthly'] = $b($f['is_bimonthly'] ?? false);
    }
    unset($f);

    $initial = [
        'family' => $fam, 'assessment' => $ass,
        'members' => array_values($members), 'finances' => array_values($finances),
        'home_needs' => array_values($homeNeeds),
    ];

    $policyJson = [
        'approval_threshold' => (float) $policy->approval_threshold,
        'rent_bonus' => (int) $policy->rent_bonus,
        'marital_bonus' => (int) $policy->marital_bonus,
        'per_eligible_person' => (int) $policy->per_eligible_person,
        'bands' => $policy->bands,
        'missing_group_size' => (int) $policy->missing_group_size,
        'missing_group_points' => (int) $policy->missing_group_points,
        'arch_points' => array_map('intval', $policy->arch_points),
    ];

    // الهويات/الهواتف المسجّلة لدى عائلات أخرى — للتنبيه الحيّ (المصدر المعتمد هو تحقّق السيرفر).
    $otherFamilies = \App\Models\Family::when($editing, fn ($q) => $q->whereKeyNot($a->family_id))
        ->get(['id', 'husband_name', 'wife_name', 'husband_id', 'wife_id', 'husband_phone', 'wife_phone']);
    $existingIds = [];
    $existingPhones = [];
    foreach ($otherFamilies as $of) {
        $label = $of->husband_name ?: ($of->wife_name ?: ('عائلة #' . $of->id));
        foreach ([$of->husband_id, $of->wife_id] as $idv) { if (filled($idv)) $existingIds[(string) $idv] = $label; }
        foreach ([$of->husband_phone, $of->wife_phone] as $pv) { if (filled($pv)) $existingPhones[(string) $pv] = $label; }
    }
    $existing = ['ids' => $existingIds, 'phones' => $existingPhones];
@endphp

<div x-data="assessmentForm({{ Illuminate\Support\Js::from($initial) }}, {{ Illuminate\Support\Js::from($policyJson) }}, {{ Illuminate\Support\Js::from($existing) }})" x-cloak
     x-effect="enforceOrphans(); attempted && refreshValidation()"
     class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- تنبيه الأخطاء (عميل) — يظهر فوراً قبل الإرسال ويحدّد ما ينقص --}}
    <div x-show="errors.length" x-cloak x-transition.opacity
         class="lg:col-span-3 bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2 text-red-700 font-semibold">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <span x-text="`يرجى تصحيح ${errors.length} ${errors.length === 1 ? 'خطأ' : 'أخطاء'} قبل الحفظ:`"></span>
            </div>
            <button type="button" @click="errors = []" class="text-red-400 hover:text-red-600 text-lg leading-none">×</button>
        </div>
        <ul class="list-disc pr-7 mt-2 text-sm text-red-600 space-y-0.5">
            <template x-for="(er, i) in errors" :key="i"><li x-text="er"></li></template>
        </ul>
    </div>

    <form method="POST" action="{{ $editing ? route('assessments.update', $a) : route('assessments.store') }}"
          class="lg:col-span-2 space-y-6" @submit="if (! validateForm()) $event.preventDefault()">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold">{{ $editing ? 'تعديل تقييم' : 'تقييم جديد' }}</h2>
            <a href="{{ $editing ? route('assessments.show', $a) : route('assessments.index') }}" class="text-gray-500 hover:underline text-sm">→ رجوع</a>
        </div>

        {{-- منفذو الزيارة --}}
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-4 text-indigo-700">منفذو الزيارة</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs text-gray-500 mb-1">تاريخ الزيارة</label>
                    <input type="date" name="assessment[visit_date]" x-model="form.assessment.visit_date" class="input" :class="ring('visit_date')"></div>
                <div><label class="block text-xs text-gray-500 mb-1">الزائرون</label>
                    <input name="assessment[visitors]" x-model="form.assessment.visitors" class="input" :class="ring('visitors')"></div>
                <div><label class="block text-xs text-gray-500 mb-1">تاريخ الزيارة التالية</label>
                    <input type="date" name="assessment[next_visit_date]" x-model="form.assessment.next_visit_date" class="input">
                    <p class="text-[10px] text-gray-400 mt-1">يُملأ تلقائياً = تاريخ الزيارة + 6 أشهر، وقابل للتعديل.</p></div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">المسؤول عن العائلة (اختياري)</label>
                    <select name="family[supervisor_id]" x-model="form.family.supervisor_id" class="input">
                        <option value="">— بلا مسؤول —</option>
                        @foreach ($supervisors as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-gray-400 mt-1">يمكن تركه فارغاً ثم تحديده بعد قبول العائلة. <a href="{{ route('supervisors.index') }}" target="_blank" class="text-indigo-600 hover:underline">إدارة المسؤولين</a></p>
                </div>
            </div>
        </div>

        {{-- العائلة --}}
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-4 text-indigo-700">بيانات العائلة (ثابتة)</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs text-gray-500 mb-1">اسم الزوج</label>
                    <input name="family[husband_name]" x-model="form.family.husband_name" class="input" :class="ring('family.husband_name')"></div>
                <div><label class="block text-xs text-gray-500 mb-1">اسم الزوجة</label>
                    <input name="family[wife_name]" x-model="form.family.wife_name" class="input" :class="ring('family.wife_name')"></div>
                <div><label class="block text-xs text-gray-500 mb-1">هوية الزوج</label>
                    <input name="family[husband_id]" x-model="form.family.husband_id" class="input" :class="[dupId(form.family.husband_id) && 'border-red-400', ring('family.husband_id')]">
                    <p x-show="dupId(form.family.husband_id)" x-cloak class="text-[11px] text-red-600 mt-1">⚠ مُسجَّل مسبقاً لدى: <span x-text="dupId(form.family.husband_id)"></span></p></div>
                <div><label class="block text-xs text-gray-500 mb-1">هوية الزوجة</label>
                    <input name="family[wife_id]" x-model="form.family.wife_id" class="input" :class="[dupId(form.family.wife_id) && 'border-red-400', ring('family.wife_id')]">
                    <p x-show="dupId(form.family.wife_id)" x-cloak class="text-[11px] text-red-600 mt-1">⚠ مُسجَّل مسبقاً لدى: <span x-text="dupId(form.family.wife_id)"></span></p></div>
                <div><label class="block text-xs text-gray-500 mb-1">هاتف الزوج</label>
                    <input name="family[husband_phone]" x-model="form.family.husband_phone" class="input" :class="[dupPhone(form.family.husband_phone) && 'border-red-400', ring('family.husband_phone')]">
                    <p x-show="dupPhone(form.family.husband_phone)" x-cloak class="text-[11px] text-red-600 mt-1">⚠ مُسجَّل مسبقاً لدى: <span x-text="dupPhone(form.family.husband_phone)"></span></p></div>
                <div><label class="block text-xs text-gray-500 mb-1">هاتف الزوجة</label>
                    <input name="family[wife_phone]" x-model="form.family.wife_phone" class="input" :class="[dupPhone(form.family.wife_phone) && 'border-red-400', ring('family.wife_phone')]">
                    <p x-show="dupPhone(form.family.wife_phone)" x-cloak class="text-[11px] text-red-600 mt-1">⚠ مُسجَّل مسبقاً لدى: <span x-text="dupPhone(form.family.wife_phone)"></span></p></div>
                <div><label class="block text-xs text-gray-500 mb-1">تاريخ ميلاد الزوج</label>
                    <input type="date" name="family[husband_dob]" x-model="form.family.husband_dob" class="input" :class="ring('family.husband_dob')"></div>
                <div><label class="block text-xs text-gray-500 mb-1">تاريخ ميلاد الزوجة</label>
                    <input type="date" name="family[wife_dob]" x-model="form.family.wife_dob" class="input" :class="ring('family.wife_dob')"></div>
                <div><label class="block text-xs text-gray-500 mb-1">الحالة الاجتماعية</label>
                    <select name="family[marital_status]" x-model="form.family.marital_status" class="input">
                        <option value="married">متزوج</option><option value="divorced">مطلّق</option>
                        <option value="widowed">أرمل</option><option value="abandoned">تارك للبيت</option>
                    </select></div>
                <div><label class="block text-xs text-gray-500 mb-1">صندوق المرضى (اختياري)</label>
                    <select name="family[health_fund]" x-model="form.family.health_fund" class="input">
                        <option value="">— غير محدد —</option>
                        <option value="كلاليت">كلاليت</option><option value="مكابي">مكابي</option>
                        <option value="مئوحيدت">مئوحيدت</option><option value="لؤوميت">لؤوميت</option>
                    </select></div>
                <div><label class="block text-xs text-gray-500 mb-1">البنك</label>
                    <input name="family[bank_name]" x-model="form.family.bank_name" class="input"></div>
                <label class="flex items-center gap-2 text-sm mt-1">
                    <input type="hidden" name="family[joint_account]" value="0">
                    <input type="checkbox" name="family[joint_account]" value="1" x-model="form.family.joint_account"> حساب مشترك</label>
                <div class="sm:col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">وصف العائلة (اختياري)</label>
                    <textarea name="family[description]" x-model="form.family.description" class="input" rows="3" placeholder="وصف حرّ عن وضع العائلة…"></textarea>
                </div>
            </div>
        </div>

        {{-- السكن والمنزل --}}
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-4 text-indigo-700">بيانات السكن والمنزل</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs text-gray-500 mb-1">نوع السكن</label>
                    <select name="assessment[house_type]" x-model="form.assessment.house_type" class="input">
                        <option value="own">ملك</option><option value="rent">إيجار</option>
                        <option value="family">عائلي</option><option value="other">أخرى</option>
                    </select></div>
                <div><label class="block text-xs text-gray-500 mb-1">الحالة المعمارية</label>
                    <select name="assessment[arch_condition]" x-model.number="form.assessment.arch_condition" class="input">
                        <option value="0">ممتاز</option><option value="1">جيد</option>
                        <option value="2">سيئ</option><option value="3">لا يصلح</option>
                    </select></div>
                <div><label class="block text-xs text-gray-500 mb-1">موقع المنزل</label>
                    <input name="assessment[house_location]" x-model="form.assessment.house_location" class="input"></div>
                {{-- يوجد أيتام: تلقائي — يُشتق من وجود فرد يتيم (لا يُحرَّر يدوياً) --}}
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-500">يوجد أيتام:</span>
                    <span :class="anyOrphan() ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-500'" class="px-2 py-0.5 rounded-full text-xs"
                          x-text="anyOrphan() ? 'نعم (تلقائي)' : 'لا'"></span>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="assessment[needs_repair]" value="0">
                    <input type="checkbox" name="assessment[needs_repair]" value="1" x-model="form.assessment.needs_repair"> يحتاج ترميم</label>
                <div class="sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">ملاحظات الترميم</label>
                    <textarea name="assessment[repairs_notes]" x-model="form.assessment.repairs_notes" class="input" rows="2"></textarea></div>
            </div>
        </div>

        {{-- الأبناء --}}
        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-indigo-700">الأبناء / أفراد العائلة</h3>
                <button type="button" @click="addMember()" class="text-sm text-indigo-600 hover:underline">+ إضافة فرد</button>
            </div>
            <template x-if="form.members.length === 0">
                <p class="text-sm text-gray-400">لا يوجد أفراد مضافون.</p>
            </template>
            <template x-for="(m, i) in form.members" :key="i">
                <div class="border border-gray-100 rounded-lg p-3 mb-3" :class="memberEligible(m) ? 'bg-green-50/40' : ''">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <input x-model="m.name" :name="`members[${i}][name]`" placeholder="الاسم" class="input" :class="ring('member.'+i+'.name')">
                        <div><label class="block text-[10px] text-gray-400">تاريخ الميلاد</label>
                            <input type="date" x-model="m.dob" :name="`members[${i}][dob]`" class="input" :class="ring('member.'+i+'.dob')"></div>
                        <select x-model="m.gender" :name="`members[${i}][gender]`" class="input" :class="ring('member.'+i+'.gender')"><option value="m">ذكر</option><option value="f">أنثى</option></select>
                        <input x-model="m.school" :name="`members[${i}][school]`" placeholder="المدرسة" class="input">
                        <input x-model="m.tutor_subject" :name="`members[${i}][tutor_subject]`" placeholder="مادة الدعم" class="input">
                        <input x-model="m.marital_status" :name="`members[${i}][marital_status]`" placeholder="الحالة الاجتماعية" class="input">
                    </div>
                    <div class="flex flex-wrap items-center gap-4 mt-2 text-sm">
                        <input type="hidden" :name="`members[${i}][contributes]`" value="0">
                        <label class="flex items-center gap-1"><input type="checkbox" :name="`members[${i}][contributes]`" value="1" x-model="m.contributes"> يعمل ويساهم</label>
                        <input type="hidden" :name="`members[${i}][higher_education]`" value="0">
                        <label class="flex items-center gap-1"><input type="checkbox" :name="`members[${i}][higher_education]`" value="1" x-model="m.higher_education"> طالب جامعي</label>
                        <input type="hidden" :name="`members[${i}][needs_tutoring]`" value="0">
                        <label class="flex items-center gap-1"><input type="checkbox" :name="`members[${i}][needs_tutoring]`" value="1" x-model="m.needs_tutoring"> يحتاج دعم</label>
                        {{-- يتيم: يُحدَّد ويُقفَل آلياً عند (أرمل + عمر<15)، وقابل للتعديل يدوياً خلاف ذلك.
                             القيمة تُرسَل عبر الحقل المخفي دائماً (لأن الـ checkbox قد يكون معطّلاً). --}}
                        <input type="hidden" :name="`members[${i}][is_orphan]`" :value="m.is_orphan ? 1 : 0">
                        <label class="flex items-center gap-1" :class="orphanForced(m) ? 'text-purple-700 font-medium' : ''">
                            <input type="checkbox" x-model="m.is_orphan" :disabled="orphanForced(m)"> يتيم
                            <span x-show="orphanForced(m)" x-cloak class="text-[10px] text-purple-500">(تلقائي)</span>
                        </label>
                        <span class="text-xs" :class="memberEligible(m) ? 'text-green-700' : 'text-gray-400'"
                              x-text="(ageOf(m.dob)!==null ? ('العمر '+ageOf(m.dob)+' · ') : '') + (memberEligible(m) ? 'مستحق' : 'غير مستحق')"></span>
                        <button type="button" @click="removeMember(i)" class="text-red-500 hover:underline mr-auto">حذف</button>
                    </div>
                </div>
            </template>
        </div>

        {{-- المدخولات --}}
        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-green-700">المدخولات</h3>
                <button type="button" @click="addFinance('income')" class="text-sm text-green-600 hover:underline">+ إضافة مدخول</button>
            </div>
            <template x-if="incomeRows().length === 0">
                <p class="text-sm text-gray-400">لا توجد مدخولات.</p>
            </template>
            <template x-for="row in incomeRows()" :key="row.i">
                <div class="flex flex-wrap items-center gap-2 mb-2 border-b border-gray-50 pb-2">
                    <input type="hidden" :name="`finances[${row.i}][type]`" value="income">
                    <input x-model="row.f.category" :name="`finances[${row.i}][category]`" placeholder="مصدر الدخل (راتب، مساعدة…)" class="input flex-1 min-w-[140px]" :class="ring('finance.'+row.i+'.category')">
                    <input type="number" x-model="row.f.amount" :name="`finances[${row.i}][amount]`" placeholder="المبلغ" class="input w-28" :class="ring('finance.'+row.i+'.amount')">
                    <input x-model="row.f.notes" :name="`finances[${row.i}][notes]`" placeholder="ملاحظة" class="input w-32">
                    <button type="button" @click="removeFinance(row.i)" class="text-red-500 hover:underline text-sm">حذف</button>
                </div>
            </template>
        </div>

        {{-- المصروفات --}}
        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-red-700">المصروفات</h3>
                <button type="button" @click="addFinance('expense')" class="text-sm text-red-600 hover:underline">+ إضافة مصروف</button>
            </div>
            <template x-if="expenseRows().length === 0">
                <p class="text-sm text-gray-400">لا توجد مصروفات.</p>
            </template>
            <template x-for="row in expenseRows()" :key="row.i">
                <div class="flex flex-wrap items-center gap-2 mb-2 border-b border-gray-50 pb-2">
                    <input type="hidden" :name="`finances[${row.i}][type]`" value="expense">
                    <input x-model="row.f.category" :name="`finances[${row.i}][category]`" placeholder="البند (إيجار، كهرباء…)" class="input flex-1 min-w-[140px]" :class="ring('finance.'+row.i+'.category')">
                    <input type="number" x-model="row.f.amount" :name="`finances[${row.i}][amount]`" placeholder="المبلغ" class="input w-28" :class="ring('finance.'+row.i+'.amount')">
                    <label class="flex items-center gap-1 text-xs">
                        <input type="hidden" :name="`finances[${row.i}][is_bimonthly]`" value="0">
                        <input type="checkbox" :name="`finances[${row.i}][is_bimonthly]`" value="1" x-model="row.f.is_bimonthly"> كل شهرين</label>
                    <input x-model="row.f.notes" :name="`finances[${row.i}][notes]`" placeholder="ملاحظة" class="input w-32">
                    <button type="button" @click="removeFinance(row.i)" class="text-red-500 hover:underline text-sm">حذف</button>
                </div>
            </template>
        </div>

        {{-- النواقص --}}
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold text-indigo-700 mb-4">النواقص الأساسية للمنزل</h3>
            <div class="flex gap-2 mb-3">
                <input x-model="needInput" @keydown.enter.prevent="addNeed()" placeholder="أضف بنداً ناقصاً واضغط Enter" class="input flex-1">
                <button type="button" @click="addNeed()" class="bg-gray-100 px-3 rounded-lg text-sm">إضافة</button>
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="(n, i) in form.home_needs" :key="i">
                    <span class="bg-amber-50 text-amber-800 px-3 py-1 rounded-full text-sm flex items-center gap-2">
                        <input type="hidden" name="home_needs[]" :value="n">
                        <span x-text="n"></span>
                        <button type="button" @click="removeNeed(i)" class="text-amber-500 hover:text-red-500">×</button>
                    </span>
                </template>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg font-medium">حفظ التقييم</button>
            <a href="{{ $editing ? route('assessments.show', $a) : route('assessments.index') }}" class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-600">إلغاء</a>
        </div>
    </form>

    {{-- لوحة المعاينة الحيّة --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm p-5 sticky top-6">
            <h3 class="font-semibold text-indigo-700 mb-1">معاينة تقديرية</h3>
            <p class="text-[11px] text-gray-400 mb-4">القيمة المعتمدة تُحسب في السيرفر بعد الحفظ.</p>
            <template x-if="preview()">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">إجمالي المدخول</span><span x-text="money(preview().income)"></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">إجمالي المصروف (شهري)</span><span x-text="money(preview().expense)"></span></div>
                    <div class="flex justify-between border-t pt-2"><span class="text-gray-500">المتبقي للعائلة</span><span x-text="money(preview().familyRemaining)"></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">عدد المستحقين</span><span x-text="preview().count"></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">المتبقي للفرد</span><span class="font-medium" x-text="money(preview().perPerson)"></span></div>
                    <div class="flex justify-between items-center border-t pt-3">
                        <span class="text-gray-500">النقاط التقديرية</span>
                        <span class="text-2xl font-bold text-indigo-700" x-text="preview().score"></span>
                    </div>
                    <div class="mt-2 text-center">
                        <span :class="preview().recommended ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="px-3 py-1 rounded-full text-sm"
                              x-text="preview().recommended ? 'توصية: مستحق' : 'توصية: غير مستحق'"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function assessmentForm(initial, policy, existing) {
    return {
        form: initial,
        policy: policy,
        existing: existing,
        needInput: '',
        errors: [],
        invalid: {},        // خريطة الحقول الناقصة لتلوين إطارها أحمر
        attempted: false,   // بعد أول محاولة حفظ: يتحدّث التحقّق حيّاً

        // قيمة نصية غير فارغة بعد إزالة الفراغات
        trimmed(v) { return (v ?? '').toString().trim(); },

        // صنف الإطار الأحمر إن كان الحقل ناقصاً
        ring(key) { return this.invalid[key] ? 'border-red-400 bg-red-50' : ''; },

        // اكتمال بيانات أحد الزوجين: اسم + هوية + هاتف + ميلاد
        parentComplete(side) {
            const f = this.form.family;
            return !!this.trimmed(f[side + '_name']) && !!this.trimmed(f[side + '_id'])
                && !!this.trimmed(f[side + '_phone']) && !!this.trimmed(f[side + '_dob']);
        },

        // يبني قائمة الأخطاء وخريطة الحقول الناقصة (بدون أعراض جانبية)
        computeErrors() {
            const e = [];
            const inv = {};
            const f = this.form;

            // منفذو الزيارة
            if (!this.trimmed(f.assessment.visit_date)) { e.push('تاريخ الزيارة مطلوب.'); inv['visit_date'] = true; }
            if (!this.trimmed(f.assessment.visitors))   { e.push('اسم منفّذ الزيارة مطلوب.'); inv['visitors'] = true; }

            // العائلة: أحد الزوجين كامل على الأقل — نلوّن الحقول الناقصة للطرف الذي بدأ المستخدم بتعبئته
            if (!this.parentComplete('husband') && !this.parentComplete('wife')) {
                e.push('بيانات العائلة: يجب إكمال بيانات أحد الزوجين (الاسم، الهوية، الهاتف، تاريخ الميلاد).');
                const fields = ['name', 'id', 'phone', 'dob'];
                const started = (s) => fields.some(k => this.trimmed(f.family[s + '_' + k]));
                let sides = [];
                if (started('husband')) sides.push('husband');
                if (started('wife')) sides.push('wife');
                if (sides.length === 0) sides = ['husband'];   // لم يبدأ بأي طرف → تلميح لطرف الزوج
                for (const s of sides) for (const k of fields) {
                    if (!this.trimmed(f.family[s + '_' + k])) inv['family.' + s + '_' + k] = true;
                }
            }

            // الأبناء: لكل فرد مُضاف — الاسم وتاريخ الميلاد والنوع
            f.members.forEach((m, i) => {
                const n = i + 1;
                if (!this.trimmed(m.name))   { e.push(`الفرد #${n}: الاسم مطلوب.`); inv['member.' + i + '.name'] = true; }
                if (!this.trimmed(m.dob))    { e.push(`الفرد #${n}: تاريخ الميلاد مطلوب.`); inv['member.' + i + '.dob'] = true; }
                if (!this.trimmed(m.gender)) { e.push(`الفرد #${n}: النوع (ذكر/أنثى) مطلوب.`); inv['member.' + i + '.gender'] = true; }
            });

            // المالية: لكل بند مُضاف — الوصف ومبلغ رقمي
            let inc = 0, exp = 0;
            f.finances.forEach((x, idx) => {
                const label = x.type === 'income' ? `المدخول #${++inc}` : `المصروف #${++exp}`;
                if (!this.trimmed(x.category)) { e.push(`${label}: الوصف مطلوب.`); inv['finance.' + idx + '.category'] = true; }
                if (this.trimmed(x.amount) === '') { e.push(`${label}: المبلغ مطلوب.`); inv['finance.' + idx + '.amount'] = true; }
                else if (isNaN(Number(x.amount))) { e.push(`${label}: المبلغ يجب أن يكون رقماً.`); inv['finance.' + idx + '.amount'] = true; }
                else if (Number(x.amount) < 0) { e.push(`${label}: المبلغ لا يمكن أن يكون سالباً.`); inv['finance.' + idx + '.amount'] = true; }
            });

            return { e, inv };
        },

        // تحديث حيّ (بعد أول محاولة) — يحدّث البانر والإطارات دون تمرير الشاشة
        refreshValidation() {
            const { e, inv } = this.computeErrors();
            this.errors = e;
            this.invalid = inv;
        },

        // تحقّق عند الإرسال — يمنع الحفظ ويمرّر للأعلى عند وجود أخطاء
        validateForm() {
            this.attempted = true;
            const { e, inv } = this.computeErrors();
            this.errors = e;
            this.invalid = inv;
            if (e.length) window.scrollTo({ top: 0, behavior: 'smooth' });
            return e.length === 0;
        },

        // تنبيه حيّ: هل القيمة مسجّلة لدى عائلة أخرى؟ يعيد اسم العائلة أو null.
        dupId(v) { v = (v || '').trim(); return v && this.existing.ids[v] ? this.existing.ids[v] : null; },
        dupPhone(v) { v = (v || '').trim(); return v && this.existing.phones[v] ? this.existing.phones[v] : null; },

        init() {
            // الزيارة التالية = تاريخ الزيارة + 6 أشهر، إن لم تُحدَّد يدوياً.
            this.$watch('form.assessment.visit_date', (v) => {
                if (v && !this.form.assessment.next_visit_date) {
                    this.form.assessment.next_visit_date = this.plusMonths(v, 6);
                }
            });
        },
        plusMonths(dateStr, n) {
            const d = new Date(dateStr);
            if (isNaN(d)) return '';
            d.setMonth(d.getMonth() + n);
            return d.toISOString().slice(0, 10);
        },

        addMember() {
            this.form.members.push({
                name: '', dob: '', gender: 'm', school: '', needs_tutoring: false,
                tutor_subject: '', higher_education: false, marital_status: '', contributes: false,
                is_orphan: false,
            });
        },
        removeMember(i) { this.form.members.splice(i, 1); },

        // الشرط الآلي: أرمل + عمر<15 → يتيم إلزامي (يُقفَل الـ checkbox).
        orphanForced(m) {
            const age = this.ageOf(m.dob);
            return this.form.family.marital_status === 'widowed' && age !== null && age < 15;
        },
        // فرض القاعدة تفاعلياً عند تغيّر الحالة الاجتماعية أو تاريخ ميلاد أي فرد (إضافة فقط).
        enforceOrphans() {
            for (const m of this.form.members) {
                if (this.orphanForced(m) && m.is_orphan !== true) m.is_orphan = true;
            }
        },
        anyOrphan() { return this.form.members.some(m => m.is_orphan); },
        addFinance(type) { this.form.finances.push({ type, category: '', amount: '', is_bimonthly: false, notes: '' }); },
        removeFinance(i) { this.form.finances.splice(i, 1); },
        // صفوف كل نوع مع الاحتفاظ بفهرسها الأصلي في form.finances (لأسماء الحقول الفريدة)
        incomeRows() { return this.form.finances.map((f, i) => ({ f, i })).filter(r => r.f.type === 'income'); },
        expenseRows() { return this.form.finances.map((f, i) => ({ f, i })).filter(r => r.f.type === 'expense'); },
        addNeed() {
            const v = this.needInput.trim();
            if (v) { this.form.home_needs.push(v); this.needInput = ''; }
        },
        removeNeed(i) { this.form.home_needs.splice(i, 1); },

        // ---------- معاينة حيّة (تقديرية — القيمة المعتمدة من السيرفر) ----------
        ageOf(dob) {
            if (!dob) return null;
            const d = new Date(dob);
            if (isNaN(d)) return null;
            const t = new Date();
            let a = t.getFullYear() - d.getFullYear();
            const mm = t.getMonth() - d.getMonth();
            if (mm < 0 || (mm === 0 && t.getDate() < d.getDate())) a--;
            return a;
        },
        memberEligible(m) {
            const age = this.ageOf(m.dob);
            if (age === null) return false;
            if (age < 18) return true;
            if (m.gender === 'f' && !m.contributes) return true;
            if (m.contributes) return true;
            if (m.higher_education) return true;
            return false;
        },
        preview() {
            const p = this.policy;
            if (!p) return null;
            const f = this.form;
            let expense = 0, income = 0;
            for (const x of f.finances) {
                const amt = Number(x.amount) || 0;
                if (x.type === 'expense') expense += x.is_bimonthly ? amt / 2 : amt;
                else income += amt;
            }
            const familyRemaining = income - expense;
            let count = f.family.marital_status === 'married' ? 2 : 1;
            for (const m of f.members) if (this.memberEligible(m)) count++;
            count = Math.max(count, 1);
            const perPerson = familyRemaining / count;

            let score = 0;
            if (f.assessment.house_type === 'rent') score += Number(p.rent_bonus);
            if (['divorced', 'widowed', 'abandoned'].includes(f.family.marital_status)) score += Number(p.marital_bonus);
            score += count * Number(p.per_eligible_person);
            let bandPts = Number(p.bands[p.bands.length - 1].points);
            for (const bnd of p.bands) { if (bnd.max !== null && perPerson < Number(bnd.max)) { bandPts = Number(bnd.points); break; } }
            score += bandPts;
            score += Math.floor(f.home_needs.length / Math.max(Number(p.missing_group_size), 1)) * Number(p.missing_group_points);
            score += Number(p.arch_points[f.assessment.arch_condition] || 0);

            const recommended = perPerson <= Number(p.approval_threshold);
            return { expense, income, familyRemaining, count, perPerson, score, recommended };
        },
        money(n) { return (Number(n) || 0).toLocaleString('ar-EG', { maximumFractionDigits: 2 }) + ' ₪'; },
    };
}
</script>
@endpush
