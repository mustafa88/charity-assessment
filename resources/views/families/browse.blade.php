@extends('layouts.main')

@php
    $maritalLabels = [
        'married'   => 'متزوج',
        'divorced'  => 'مطلّق',
        'widowed'   => 'أرمل',
        'abandoned' => 'متروك',
    ];
    $houseLabels = ['own' => 'ملك', 'rent' => 'إيجار', 'family' => 'عائلي', 'other' => 'أخرى'];

    // مجموعتا التصفية: حسب الميزة، وحسب الحالة الاجتماعية.
    $featureTabs = [
        'orphans' => ['label' => 'فيها أيتام',  'on' => 'border-rose-600 text-rose-700',  'badge' => 'bg-rose-100 text-rose-700'],
        'repair'  => ['label' => 'تحتاج ترميم', 'on' => 'border-amber-600 text-amber-700', 'badge' => 'bg-amber-100 text-amber-700'],
    ];
    $titles = array_merge(
        ['orphans' => 'العائلات التي فيها أيتام', 'repair' => 'العائلات التي تحتاج ترميم'],
        array_map(fn ($l) => "العائلات — $l", $maritalLabels)
    );
@endphp

@section('title', $titles[$filter] ?? 'تصفّح العائلات')

@section('content')
<div class="flex items-center justify-between mb-1">
    <h2 class="text-xl font-semibold">{{ $titles[$filter] ?? 'تصفّح العائلات' }}</h2>
    <span class="text-sm text-gray-400">{{ $rows->count() }} عائلة</span>
</div>
<p class="text-xs text-gray-400 mb-4">ضمن <span class="text-gray-500">العائلات المقبولة فقط</span> (أحدث تقييم قراره «مقبول»).</p>

{{-- تبويبات حسب الميزة --}}
<div class="flex items-center gap-1 mb-3 border-b border-gray-200">
    @foreach ($featureTabs as $key => $tab)
        @php $on = $key === $filter; @endphp
        <a href="{{ route('families.browse', ['filter' => $key]) }}"
           class="px-4 py-2 -mb-px border-b-2 text-sm flex items-center gap-2
                  {{ $on ? $tab['on'].' font-medium' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            {{ $tab['label'] }}
            <span class="px-2 py-0.5 rounded-full text-xs {{ $on ? $tab['badge'] : 'bg-gray-100 text-gray-400' }}">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</div>

{{-- تصفية حسب الحالة الاجتماعية --}}
<div class="flex items-center flex-wrap gap-2 mb-5">
    <span class="text-xs text-gray-400 ml-1">حسب الحالة الاجتماعية:</span>
    @foreach ($maritalLabels as $key => $label)
        @php $on = $key === $filter; @endphp
        <a href="{{ route('families.browse', ['filter' => $key]) }}"
           class="px-3 py-1 rounded-full text-xs border flex items-center gap-1.5
                  {{ $on ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-gray-200 text-gray-600 hover:border-indigo-300' }}">
            {{ $label }}
            <span class="px-1.5 rounded-full {{ $on ? 'bg-white/25' : 'bg-gray-100 text-gray-400' }}">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</div>

@if ($rows->isEmpty())
    <div class="bg-white rounded-lg p-8 text-center text-gray-400">لا توجد عائلات مطابقة.</div>
@else
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">#</th>
                    <th class="px-4 py-3 font-medium">العائلة</th>
                    <th class="px-4 py-3 font-medium">الهوية</th>
                    <th class="px-4 py-3 font-medium">الحالة الاجتماعية</th>
                    <th class="px-4 py-3 font-medium">السكن</th>
                    <th class="px-4 py-3 font-medium">الأبناء</th>
                    <th class="px-4 py-3 font-medium">الأيتام</th>
                    <th class="px-4 py-3 font-medium">ترميم</th>
                    <th class="px-4 py-3 font-medium">المسؤول</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($rows as $i => $row)
                    @php $f = $row->family; $a = $row->assessment; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-medium">{{ $f->husband_name ?: ($f->wife_name ?: '—') }}</td>
                        <td class="px-4 py-3 text-gray-500" dir="ltr">{{ $f->husband_id ?: ($f->wife_id ?: '—') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $maritalLabels[$f->marital_status] ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $houseLabels[$a->house_type] ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $row->children }}</td>
                        <td class="px-4 py-3">
                            @if ($row->orphans > 0)
                                <span class="bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full text-xs">{{ $row->orphans }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($a->needs_repair)
                                <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-xs">نعم</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $f->supervisor?->name ?: '— بلا مسؤول —' }}</td>
                        <td class="px-4 py-3 text-left whitespace-nowrap">
                            <a href="{{ route('assessments.show', $a) }}" class="text-indigo-600 hover:underline">عرض</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
