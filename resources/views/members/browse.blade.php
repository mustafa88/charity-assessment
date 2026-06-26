@extends('layouts.main')

@php
    $genderLabels = ['m' => 'ولد', 'f' => 'بنت'];
    $tabs = [
        'children'         => ['label' => 'كل الأولاد', 'on' => 'border-sky-600 text-sky-700',       'badge' => 'bg-sky-100 text-sky-700',       'title' => 'جميع الأولاد (بنات + أولاد)'],
        'orphans'          => ['label' => 'الأيتام',      'on' => 'border-rose-600 text-rose-700',     'badge' => 'bg-rose-100 text-rose-700',     'title' => 'جميع الأيتام (بنات + أولاد)'],
        'higher_education' => ['label' => 'طالب جامعي',   'on' => 'border-indigo-600 text-indigo-700', 'badge' => 'bg-indigo-100 text-indigo-700', 'title' => 'الأولاد طلاب الجامعة'],
        'tutoring'         => ['label' => 'يحتاج دعم',    'on' => 'border-amber-600 text-amber-700',   'badge' => 'bg-amber-100 text-amber-700',   'title' => 'الأولاد المحتاجون دعماً (دروس تقوية)'],
        'contributes'      => ['label' => 'يعمل/يساهم',   'on' => 'border-teal-600 text-teal-700',     'badge' => 'bg-teal-100 text-teal-700',     'title' => 'الأولاد الذين يعملون ويساهمون'],
    ];
    $current = $tabs[$filter];
@endphp

@section('title', $current['title'])

@section('content')
<div class="flex items-center justify-between mb-1">
    <h2 class="text-xl font-semibold">{{ $current['title'] }}</h2>
    <span class="text-sm text-gray-400">{{ $rows->count() }} فرد</span>
</div>
<p class="text-xs text-gray-400 mb-4">ضمن <span class="text-gray-500">العائلات المقبولة فقط</span> (أحدث تقييم قراره «مقبول»).</p>

{{-- تبويبات --}}
<div class="flex items-center flex-wrap gap-1 mb-5 border-b border-gray-200">
    @foreach ($tabs as $key => $tab)
        @php $on = $key === $filter; @endphp
        <a href="{{ route('members.browse', ['filter' => $key]) }}"
           class="px-4 py-2 -mb-px border-b-2 text-sm flex items-center gap-2
                  {{ $on ? $tab['on'].' font-medium' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            {{ $tab['label'] }}
            <span class="px-2 py-0.5 rounded-full text-xs {{ $on ? $tab['badge'] : 'bg-gray-100 text-gray-400' }}">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</div>

@if ($rows->isEmpty())
    <div class="bg-white rounded-lg p-8 text-center text-gray-400">لا يوجد أفراد مطابقون.</div>
@else
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">#</th>
                    <th class="px-4 py-3 font-medium">الاسم</th>
                    <th class="px-4 py-3 font-medium">العمر</th>
                    <th class="px-4 py-3 font-medium">ولد/بنت</th>
                    <th class="px-4 py-3 font-medium">الحالة</th>
                    <th class="px-4 py-3 font-medium">المدرسة/الدراسة</th>
                    <th class="px-4 py-3 font-medium">العائلة</th>
                    <th class="px-4 py-3 font-medium">اسم الأم</th>
                    <th class="px-4 py-3 font-medium">الهاتف</th>
                    <th class="px-4 py-3 font-medium">المسؤول</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($rows as $i => $m)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-medium">{{ $m->name ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs">{{ $m->age !== null ? $m->age . ' سنة' : '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $genderLabels[$m->gender] ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if ($m->is_orphan)
                                <span class="bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full text-xs">يتيم</span>
                            @endif
                            @if ($m->higher_education)
                                <span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full text-xs">جامعي</span>
                            @endif
                            @if ($m->contributes)
                                <span class="bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full text-xs">يعمل</span>
                            @endif
                            @if ($m->needs_tutoring)
                                <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-xs">دروس تقوية</span>
                            @endif
                            @if (! $m->is_orphan && ! $m->higher_education && ! $m->contributes && ! $m->needs_tutoring)
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $m->school ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $m->family }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $m->mother ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500" dir="ltr">{{ $m->phone ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $m->supervisor ?: '— بلا مسؤول —' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
