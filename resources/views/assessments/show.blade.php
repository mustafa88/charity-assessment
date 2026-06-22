@extends('layouts.main')
@section('title', $a->family->husband_name ?: ($a->family->wife_name ?: 'تقييم'))

@php
    $houseLabels    = ['own' => 'ملك', 'rent' => 'إيجار', 'family' => 'عائلي', 'other' => 'أخرى'];
    $maritalLabels  = ['married' => 'متزوج', 'divorced' => 'مطلّق', 'widowed' => 'أرمل', 'abandoned' => 'تارك للبيت'];
    $genderLabels   = ['m' => 'ذكر', 'f' => 'أنثى'];
    $archLabels     = ['ممتاز', 'جيد', 'سيئ', 'لا يصلح'];
    $decisionLabels = ['pending' => 'قيد الانتظار', 'accepted' => 'مقبول', 'rejected' => 'مرفوض'];
    $decisionClass  = ['pending' => 'bg-gray-100 text-gray-700', 'accepted' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
    $outdated       = $active && $a->policy && $a->policy->version < $active->version;
    $fam            = $a->family;
    $incomes        = $a->finances->where('type', 'income');
    $expenses       = $a->finances->where('type', 'expense');
@endphp

{{-- صف بيانات (تسمية ↔ قيمة) موحّد لكل البطاقات --}}
@php
    $row = fn ($label, $value) =>
        '<div class="flex justify-between gap-3 border-b border-gray-50 py-1">'
        .'<span class="text-gray-500 shrink-0">'.$label.'</span>'
        .'<span class="text-left">'.($value === null || $value === '' ? '—' : e($value)).'</span></div>';
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">{{ $fam->husband_name ?: ($fam->wife_name ?: 'تقييم') }}</h2>
        <div class="flex gap-3 text-sm items-center">
            <a href="{{ route('assessments.pdf', $a) }}" target="_blank"
               class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg">⬇ طباعة PDF</a>
            <a href="{{ route('assessments.edit', $a) }}" class="text-gray-500 hover:underline">تعديل</a>
            <a href="{{ route('assessments.index') }}" class="text-gray-500 hover:underline">→ رجوع</a>
        </div>
    </div>

    {{-- بطاقات النتيجة --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <div class="text-3xl font-bold text-indigo-700">{{ $a->total_score }}</div>
            <div class="text-xs text-gray-500 mt-1">النقاط</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <div class="text-lg font-bold">@money($a->per_person_remaining)</div>
            <div class="text-xs text-gray-500 mt-1">المتبقي للفرد</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <span class="px-2 py-1 rounded-full text-sm {{ $a->recommended ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                {{ $a->recommended ? 'مستحق' : 'غير مستحق' }}
            </span>
            <div class="text-xs text-gray-500 mt-2">التوصية الآلية</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 text-center">
            <span class="px-2 py-1 rounded-full text-sm {{ $decisionClass[$a->decision] ?? '' }}">{{ $decisionLabels[$a->decision] ?? $a->decision }}</span>
            <div class="text-xs text-gray-500 mt-2">القرار اليدوي</div>
        </div>
    </div>

    {{-- منفذو الزيارة --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-3">منفذو الزيارة</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 text-sm">
            {!! $row('تاريخ الزيارة', $a->visit_date?->toDateString()) !!}
            {!! $row('الزيارة التالية', $a->next_visit_date?->toDateString()) !!}
            {!! $row('الزائرون', $a->visitors) !!}
            {!! $row('المسؤول عن العائلة', optional($fam->supervisor)->name) !!}
            {!! $row('السياسة المعتمدة', 'v'.$a->policy?->version) !!}
        </div>
    </div>

    {{-- بيانات العائلة (ثابتة) --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-3">بيانات العائلة (ثابتة)</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 text-sm">
            <div>
                <div class="text-xs font-medium text-gray-400 mb-1">الزوج</div>
                {!! $row('الاسم', $fam->husband_name) !!}
                {!! $row('الهوية', $fam->husband_id) !!}
                {!! $row('الهاتف', $fam->husband_phone) !!}
                {!! $row('تاريخ الميلاد', $fam->husband_dob?->toDateString()) !!}
            </div>
            <div>
                <div class="text-xs font-medium text-gray-400 mb-1">الزوجة</div>
                {!! $row('الاسم', $fam->wife_name) !!}
                {!! $row('الهوية', $fam->wife_id) !!}
                {!! $row('الهاتف', $fam->wife_phone) !!}
                {!! $row('تاريخ الميلاد', $fam->wife_dob?->toDateString()) !!}
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 text-sm mt-3 pt-3 border-t border-gray-100">
            {!! $row('الحالة الاجتماعية', $maritalLabels[$fam->marital_status] ?? $fam->marital_status) !!}
            {!! $row('صندوق المرضى', $fam->health_fund) !!}
            {!! $row('البنك', $fam->bank_name) !!}
            {!! $row('حساب مشترك', $fam->joint_account ? 'نعم' : 'لا') !!}
        </div>
        @if ($fam->description)
            <div class="mt-3 pt-3 border-t border-gray-100 text-sm">
                <div class="text-gray-500 mb-1">وصف العائلة</div>
                <p class="whitespace-pre-line">{{ $fam->description }}</p>
            </div>
        @endif
    </div>

    {{-- بيانات السكن والمنزل --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-3">بيانات السكن والمنزل</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 text-sm">
            {!! $row('نوع السكن', $houseLabels[$a->house_type] ?? $a->house_type) !!}
            {!! $row('الحالة المعمارية', $archLabels[$a->arch_condition] ?? $a->arch_condition) !!}
            {!! $row('موقع المنزل', $a->house_location) !!}
            {!! $row('يوجد أيتام', $a->has_orphans ? 'نعم' : 'لا') !!}
            {!! $row('يحتاج ترميم', $a->needs_repair ? 'نعم' : 'لا') !!}
        </div>
        @if ($a->repairs_notes)
            <div class="mt-3 pt-3 border-t border-gray-100 text-sm">
                <div class="text-gray-500 mb-1">ملاحظات الترميم</div>
                <p class="whitespace-pre-line">{{ $a->repairs_notes }}</p>
            </div>
        @endif
    </div>

    {{-- الأفراد --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-3">الأفراد / أفراد العائلة</h3>
        @if ($a->members->isEmpty())
            <p class="text-sm text-gray-400">لا أفراد.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm whitespace-nowrap">
                    <thead>
                        <tr class="text-xs text-gray-400 border-b border-gray-100">
                            <th class="py-2 text-right font-medium">الاسم</th>
                            <th class="py-2 text-right font-medium">الجنس</th>
                            <th class="py-2 text-right font-medium">الميلاد (العمر)</th>
                            <th class="py-2 text-right font-medium">المدرسة</th>
                            <th class="py-2 text-right font-medium">الحالة الاجتماعية</th>
                            <th class="py-2 text-right font-medium">سمات</th>
                            <th class="py-2 text-right font-medium">الأهلية</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($a->members as $m)
                            <tr>
                                <td class="py-2">{{ $m->name ?: '—' }}</td>
                                <td class="py-2 text-gray-500">{{ $genderLabels[$m->gender] ?? '—' }}</td>
                                <td class="py-2 text-gray-500">{{ $m->dob ? $m->dob->toDateString().' ('.$m->dob->age.' سنة)' : '—' }}</td>
                                <td class="py-2 text-gray-500">{{ $m->school ?: '—' }}</td>
                                <td class="py-2 text-gray-500">{{ $m->marital_status ?: '—' }}</td>
                                <td class="py-2">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($m->contributes)
                                            <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full text-xs">يعمل ويساهم</span>
                                        @endif
                                        @if ($m->higher_education)
                                            <span class="bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full text-xs">طالب جامعي</span>
                                        @endif
                                        @if ($m->needs_tutoring)
                                            <span class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full text-xs">دعم{{ $m->tutor_subject ? ': '.$m->tutor_subject : '' }}</span>
                                        @endif
                                        @if ($m->is_orphan)
                                            <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full text-xs">يتيم</span>
                                        @endif
                                        @if (! $m->contributes && ! $m->higher_education && ! $m->needs_tutoring && ! $m->is_orphan)
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2">
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $m->is_eligible ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">
                                        {{ $m->is_eligible ? 'مستحق' : 'غير مستحق' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- المالية: جدول لكل نوع --}}
        <div class="bg-white rounded-lg shadow-sm p-5 space-y-5">
            <div>
                <h3 class="font-semibold text-green-700 mb-3">المدخولات</h3>
                @if ($incomes->isEmpty())
                    <p class="text-sm text-gray-400">لا مدخولات.</p>
                @else
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($incomes as $f)
                                <tr>
                                    <td class="py-1">{{ $f->category }}</td>
                                    <td class="py-1 text-left text-green-700">@money($f->amount)</td>
                                    <td class="py-1 text-xs text-gray-400">{{ $f->notes }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            <div>
                <h3 class="font-semibold text-red-700 mb-3">المصروفات</h3>
                @if ($expenses->isEmpty())
                    <p class="text-sm text-gray-400">لا مصروفات.</p>
                @else
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($expenses as $f)
                                <tr>
                                    <td class="py-1">{{ $f->category }}</td>
                                    <td class="py-1 text-left text-red-700">@money($f->amount)</td>
                                    <td class="py-1 text-xs text-gray-400">{{ $f->is_bimonthly ? 'كل شهرين' : $f->notes }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- النواقص --}}
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold text-indigo-700 mb-3">النواقص الأساسية للمنزل</h3>
            @if ($a->homeNeeds->isEmpty())
                <p class="text-sm text-gray-400">لا نواقص.</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($a->homeNeeds as $h)
                        <span class="bg-amber-50 text-amber-800 px-3 py-1 rounded-full text-sm">{{ $h->item }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- القرار اليدوي --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-1">القرار النهائي</h3>
        <p class="text-xs text-gray-400 mb-4">القرار مستقل عن التوصية الآلية — يُتّخذ يدوياً.</p>
        <form method="POST" action="{{ route('assessments.decide', $a) }}" class="space-y-3">
            @csrf
            <textarea name="note" rows="2" class="input" placeholder="ملاحظة على القرار (اختياري)">{{ old('note', $a->decision_note) }}</textarea>
            <div class="flex flex-wrap gap-3">
                <button name="decision" value="accepted" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">قبول</button>
                <button name="decision" value="rejected" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">رفض</button>
                <button name="decision" value="pending" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">إعادة للانتظار</button>
            </div>
        </form>
        @if ($outdated)
            <form method="POST" action="{{ route('assessments.convert', $a) }}" class="mt-3"
                  onsubmit="return confirm('تحويل هذا التقييم إلى الإصدار الأحدث من السياسة؟ سيُعاد حساب النقاط (القرار لا يتأثر).')">
                @csrf
                <button class="border border-amber-400 text-amber-700 px-4 py-2 rounded-lg text-sm">
                    تحويل للإصدار الأحدث (v{{ $active->version }})
                </button>
            </form>
        @endif
    </div>

    {{-- ملاحظات العائلة (سجل تراكمي مؤرّخ) --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-1">ملاحظات العائلة</h3>
        <p class="text-xs text-gray-400 mb-4">سجل تراكمي لكل ما يجري ويتمّ عند العائلة — كل ملاحظة محفوظة بتاريخ كتابتها.</p>

        <form method="POST" action="{{ route('families.notes.store', $fam) }}" class="space-y-3 mb-5">
            @csrf
            <textarea name="body" rows="3" class="input" placeholder="اكتب ملاحظة جديدة…">{{ old('body') }}</textarea>
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">إضافة ملاحظة</button>
        </form>

        @if ($fam->notes->isEmpty())
            <p class="text-sm text-gray-400">لا ملاحظات بعد.</p>
        @else
            <ul class="space-y-3">
                @foreach ($fam->notes as $note)
                    <li class="border-r-2 border-indigo-100 pr-3">
                        <div class="text-xs text-gray-400 mb-1">
                            {{ $note->created_at->format('Y-m-d H:i') }}@if ($note->author) · {{ $note->author->name }}@endif
                        </div>
                        <div class="text-sm whitespace-pre-line">{{ $note->body }}</div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- مرفقات العائلة (صور / PDF) --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-1">مرفقات العائلة</h3>
        <p class="text-xs text-gray-400 mb-4">صور أو ملفات PDF خاصة بالعائلة (حتى 10 ميغابايت للملف).</p>

        <form method="POST" action="{{ route('families.attachments.store', $fam) }}" enctype="multipart/form-data" class="space-y-3 mb-5">
            @csrf
            <input type="file" name="file" accept="image/*,application/pdf" required
                   class="block text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm hover:file:bg-indigo-100">
            <input name="description" value="{{ old('description') }}" maxlength="255" class="input" placeholder="وصف الملف (اختياري) — مثل: صورة الهوية، كشف حساب…">
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">رفع المرفق</button>
        </form>

        @if ($fam->attachments->isEmpty())
            <p class="text-sm text-gray-400">لا مرفقات بعد.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach ($fam->attachments as $att)
                    <div class="border border-gray-100 rounded-lg overflow-hidden flex flex-col">
                        <a href="{{ route('attachments.show', $att) }}" target="_blank" class="block bg-gray-50 h-28 flex items-center justify-center overflow-hidden">
                            @if ($att->isImage())
                                <img src="{{ route('attachments.show', $att) }}" alt="{{ $att->original_name }}" class="object-cover w-full h-full">
                            @else
                                <span class="flex flex-col items-center text-red-500">
                                    <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="text-[10px] mt-1 font-medium">PDF</span>
                                </span>
                            @endif
                        </a>
                        <div class="p-2 flex-1 flex flex-col gap-1">
                            @if ($att->description)
                                <div class="text-xs text-gray-700 font-medium" title="{{ $att->description }}">{{ $att->description }}</div>
                            @endif
                            <a href="{{ route('attachments.show', $att) }}" target="_blank" class="text-[11px] text-indigo-600 hover:underline truncate" title="{{ $att->original_name }}">{{ $att->original_name }}</a>
                            <div class="text-[10px] text-gray-400">
                                {{ number_format($att->size / 1024, 0) }} ك.ب · {{ $att->created_at->format('Y-m-d') }}
                            </div>
                            <form method="POST" action="{{ route('attachments.destroy', $att) }}" class="mt-auto"
                                  onsubmit="return confirm('حذف المرفق «{{ $att->original_name }}»؟')">
                                @csrf @method('DELETE')
                                <button class="text-[11px] text-red-500 hover:underline">حذف</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- سجل التدقيق --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-3">سجل التدقيق</h3>
        @if ($a->audits->isEmpty())
            <p class="text-sm text-gray-400">لا سجلات.</p>
        @else
            <ul class="text-sm space-y-1">
                @foreach ($a->audits as $au)
                    <li class="flex gap-3 text-gray-600">
                        <span class="text-gray-400">{{ optional($au->created_at)->toDateString() }}</span>
                        @if ($au->action === 'decided')
                            <span>قرار: <b>{{ $decisionLabels[$au->from_decision] ?? $au->from_decision }}</b> ← <b>{{ $decisionLabels[$au->to_decision] ?? $au->to_decision }}</b></span>
                        @elseif ($au->action === 'converted_policy')
                            <span>تحويل سياسة: v{{ $au->from_version }} ← v{{ $au->to_version }} (نقاط {{ $au->from_score }}←{{ $au->to_score }})</span>
                        @elseif ($au->action === 'revisited')
                            <span>زيارة جديدة: تاريخ الزيارة <b>{{ $au->meta['from_visit_date'] ?? '—' }}</b> ← <b>{{ $au->meta['to_visit_date'] ?? '—' }}</b>@if (($au->meta['from_next_visit_date'] ?? null) !== ($au->meta['to_next_visit_date'] ?? null)) · الزيارة التالية <b>{{ $au->meta['from_next_visit_date'] ?? '—' }}</b> ← <b>{{ $au->meta['to_next_visit_date'] ?? '—' }}</b>@endif</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
