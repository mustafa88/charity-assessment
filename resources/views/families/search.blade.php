@extends('layouts.main')
@section('title', 'بحث عن عائلة')

@php
    $maritalLabels = ['married' => 'متزوج', 'divorced' => 'مطلّق', 'widowed' => 'أرمل', 'abandoned' => 'تارك للبيت'];
@endphp

@section('content')
<div class="flex items-center justify-between mb-1">
    <h2 class="text-xl font-semibold">بحث عن عائلة</h2>
    @if ($q !== '')
        <span class="text-sm text-gray-400">{{ $results->count() }} نتيجة</span>
    @endif
</div>
<p class="text-xs text-gray-400 mb-4">يشمل كل العائلات (بغضّ النظر عن القرار) — ابحث باسم الزوج أو الزوجة، رقم الهاتف، أو رقم الهوية.</p>

<form method="GET" action="{{ route('families.search') }}" class="bg-white rounded-lg shadow-sm p-4 mb-4 flex gap-3">
    <input type="text" name="q" value="{{ $q }}" autofocus
           class="input flex-1" placeholder="اسم الزوج/الزوجة، رقم الهوية، أو رقم الهاتف…">
    <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm">بحث</button>
</form>

@if ($q === '')
    <div class="bg-white rounded-lg p-8 text-center text-gray-400">اكتب اسماً أو رقم هوية أو رقم هاتف للبحث.</div>
@elseif ($results->isEmpty())
    <div class="bg-white rounded-lg p-8 text-center text-gray-400">لا توجد نتائج مطابقة لـ «{{ $q }}».</div>
@else
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">العائلة</th>
                    <th class="px-4 py-3 font-medium">الهوية</th>
                    <th class="px-4 py-3 font-medium">الهاتف</th>
                    <th class="px-4 py-3 font-medium">الحالة الاجتماعية</th>
                    <th class="px-4 py-3 font-medium">المسؤول</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($results as $f)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $f->husband_name ?: ($f->wife_name ?: '—') }}</td>
                        <td class="px-4 py-3 text-gray-500" dir="ltr">{{ $f->husband_id ?: ($f->wife_id ?: '—') }}</td>
                        <td class="px-4 py-3 text-gray-500" dir="ltr">{{ $f->husband_phone ?: ($f->wife_phone ?: '—') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $maritalLabels[$f->marital_status] ?? $f->marital_status }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ optional($f->supervisor)->name ?: '—' }}</td>
                        <td class="px-4 py-3 text-left whitespace-nowrap">
                            <a href="{{ route('families.show', $f) }}" class="text-indigo-600 hover:underline">عرض العائلة</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
