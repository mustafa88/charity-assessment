@extends('layouts.main')
@section('title', 'جميع الأيتام')

@php
    $genderLabels = ['m' => 'ولد', 'f' => 'بنت'];
@endphp

@section('content')
<div class="flex items-center justify-between mb-1">
    <h2 class="text-xl font-semibold">جميع الأيتام</h2>
    <div class="flex items-center gap-3">
        <span class="text-sm text-gray-400">{{ $orphans->count() }} يتيم</span>
        @if ($orphans->isNotEmpty())
            <a href="{{ route('orphans.pdf') }}" target="_blank"
               class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                تصدير / تنزيل PDF
            </a>
        @endif
    </div>
</div>
<p class="text-xs text-gray-400 mb-4">الأيتام ضمن <span class="text-gray-500">العائلات المقبولة فقط</span> (أحدث تقييم قراره «مقبول»).</p>

@if ($orphans->isEmpty())
    <div class="bg-white rounded-lg p-8 text-center text-gray-400">لا يوجد أيتام ضمن العائلات المقبولة.</div>
@else
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">#</th>
                    <th class="px-4 py-3 font-medium">الاسم</th>
                    <th class="px-4 py-3 font-medium">العمر</th>
                    <th class="px-4 py-3 font-medium">ولد/بنت</th>
                    <th class="px-4 py-3 font-medium">اسم الأم</th>
                    <th class="px-4 py-3 font-medium">الهاتف</th>
                    <th class="px-4 py-3 font-medium">المسؤول عن العائلة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($orphans as $i => $o)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-medium">{{ $o->name ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-xs">{{ $o->age !== null ? $o->age . ' سنة' : '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $genderLabels[$o->gender] ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $o->mother ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500" dir="ltr">{{ $o->phone ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $o->supervisor ?: '— بلا مسؤول —' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
