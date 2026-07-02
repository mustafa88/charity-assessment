@extends('layouts.main')
@section('title', $family->husband_name ?: ($family->wife_name ?: 'عائلة'))

@php
    $maritalLabels  = ['married' => 'متزوج', 'divorced' => 'مطلّق', 'widowed' => 'أرمل', 'abandoned' => 'تارك للبيت'];
    $decisionLabels = ['pending' => 'قيد الانتظار', 'accepted' => 'مقبول', 'rejected' => 'مرفوض'];
    $decisionClass  = ['pending' => 'bg-gray-100 text-gray-700', 'accepted' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
@endphp

@php
    $row = fn ($label, $value) =>
        '<div class="flex justify-between gap-3 border-b border-gray-50 py-1">'
        .'<span class="text-gray-500 shrink-0">'.$label.'</span>'
        .'<span class="text-left">'.($value === null || $value === '' ? '—' : e($value)).'</span></div>';
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold">{{ $family->husband_name ?: ($family->wife_name ?: 'عائلة') }}</h2>
        <a href="{{ route('families.search') }}" class="text-gray-500 hover:underline text-sm">→ رجوع للبحث</a>
    </div>

    {{-- بيانات العائلة (ثابتة) --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-3">بيانات العائلة (ثابتة)</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 text-sm">
            <div>
                <div class="text-xs font-medium text-gray-400 mb-1">الزوج</div>
                {!! $row('الاسم', $family->husband_name) !!}
                {!! $row('الهوية', $family->husband_id) !!}
                {!! $row('الهاتف', $family->husband_phone) !!}
                {!! $row('تاريخ الميلاد', $family->husband_dob?->toDateString()) !!}
            </div>
            <div>
                <div class="text-xs font-medium text-gray-400 mb-1">الزوجة</div>
                {!! $row('الاسم', $family->wife_name) !!}
                {!! $row('الهوية', $family->wife_id) !!}
                {!! $row('الهاتف', $family->wife_phone) !!}
                {!! $row('تاريخ الميلاد', $family->wife_dob?->toDateString()) !!}
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 text-sm mt-3 pt-3 border-t border-gray-100">
            {!! $row('الحالة الاجتماعية', $maritalLabels[$family->marital_status] ?? $family->marital_status) !!}
            {!! $row('صندوق المرضى', $family->health_fund) !!}
            {!! $row('البنك', $family->bank_name) !!}
            {!! $row('حساب مشترك', $family->joint_account ? 'نعم' : 'لا') !!}
            {!! $row('المسؤول عن العائلة', optional($family->supervisor)->name) !!}
        </div>
        @if ($family->description)
            <div class="mt-3 pt-3 border-t border-gray-100 text-sm">
                <div class="text-gray-500 mb-1">وصف العائلة</div>
                <p class="whitespace-pre-line">{{ $family->description }}</p>
            </div>
        @endif
    </div>

    {{-- تاريخ التقييمات --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-3">تاريخ التقييمات</h3>
        @if ($family->assessments->isEmpty())
            <p class="text-sm text-gray-400">لا تقييمات بعد لهذه العائلة.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm whitespace-nowrap">
                    <thead>
                        <tr class="text-xs text-gray-400 border-b border-gray-100">
                            <th class="py-2 text-right font-medium">تاريخ الزيارة</th>
                            <th class="py-2 text-right font-medium">السياسة</th>
                            <th class="py-2 text-right font-medium">النقاط</th>
                            <th class="py-2 text-right font-medium">المتبقي للفرد</th>
                            <th class="py-2 text-right font-medium">التوصية الآلية</th>
                            <th class="py-2 text-right font-medium">القرار</th>
                            <th class="py-2 text-right font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($family->assessments as $a)
                            <tr>
                                <td class="py-2">{{ $a->visit_date?->toDateString() ?: '—' }}</td>
                                <td class="py-2 text-gray-500">v{{ $a->policy?->version }}</td>
                                <td class="py-2 text-gray-500">{{ $a->total_score }}</td>
                                <td class="py-2 text-gray-500">@money($a->per_person_remaining)</td>
                                <td class="py-2">
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $a->recommended ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $a->recommended ? 'مستحق' : 'غير مستحق' }}
                                    </span>
                                </td>
                                <td class="py-2">
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $decisionClass[$a->decision] ?? '' }}">{{ $decisionLabels[$a->decision] ?? $a->decision }}</span>
                                </td>
                                <td class="py-2 text-left">
                                    <a href="{{ route('assessments.show', $a) }}" class="text-indigo-600 hover:underline">عرض</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ملاحظات العائلة (سجل تراكمي مؤرّخ) --}}
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-1">ملاحظات العائلة</h3>
        <p class="text-xs text-gray-400 mb-4">سجل تراكمي لكل ما يجري ويتمّ عند العائلة — كل ملاحظة محفوظة بتاريخ كتابتها.</p>

        <form method="POST" action="{{ route('families.notes.store', $family) }}" class="space-y-3 mb-5">
            @csrf
            <textarea name="body" rows="3" class="input" placeholder="اكتب ملاحظة جديدة…">{{ old('body') }}</textarea>
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">إضافة ملاحظة</button>
        </form>

        @if ($family->notes->isEmpty())
            <p class="text-sm text-gray-400">لا ملاحظات بعد.</p>
        @else
            <ul class="space-y-3">
                @foreach ($family->notes as $note)
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

        <form method="POST" action="{{ route('families.attachments.store', $family) }}" enctype="multipart/form-data" class="space-y-3 mb-5">
            @csrf
            <input type="file" name="file" accept="image/*,application/pdf" required
                   class="block text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm hover:file:bg-indigo-100">
            <input name="description" value="{{ old('description') }}" maxlength="255" class="input" placeholder="وصف الملف (اختياري) — مثل: صورة الهوية، كشف حساب…">
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">رفع المرفق</button>
        </form>

        @if ($family->attachments->isEmpty())
            <p class="text-sm text-gray-400">لا مرفقات بعد.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach ($family->attachments as $att)
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
</div>
@endsection
