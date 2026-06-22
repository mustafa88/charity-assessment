@extends('layouts.main')

@php
    $tabs = [
        'accepted' => ['label' => 'مقبولة',        'badge' => 'bg-green-100 text-green-700', 'active' => 'border-green-600 text-green-700'],
        'pending'  => ['label' => 'قيد الانتظار',  'badge' => 'bg-amber-100 text-amber-700', 'active' => 'border-amber-600 text-amber-700'],
        'rejected' => ['label' => 'مرفوضة',        'badge' => 'bg-red-100 text-red-700',     'active' => 'border-red-600 text-red-700'],
    ];
    $current = $tabs[$status];
@endphp

@section('title', 'التقييمات — ' . $current['label'])

@section('content')
<div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold">التقييمات <span class="text-gray-400 font-normal">— {{ $current['label'] }}</span></h2>
    <a href="{{ route('assessments.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">+ تقييم جديد</a>
</div>

{{-- تبويبات الحالات --}}
<div class="flex items-center gap-1 mb-5 border-b border-gray-200">
    @foreach ($tabs as $key => $tab)
        @php $on = $key === $status; @endphp
        <a href="{{ route('assessments.index', ['status' => $key]) }}"
           class="px-4 py-2 -mb-px border-b-2 text-sm flex items-center gap-2
                  {{ $on ? $tab['active'].' font-medium' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            {{ $tab['label'] }}
            <span class="px-2 py-0.5 rounded-full text-xs {{ $on ? $tab['badge'] : 'bg-gray-100 text-gray-400' }}">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</div>

@if ($assessments->isEmpty())
    <div class="bg-white rounded-lg p-8 text-center text-gray-400">لا توجد عائلات ضمن «{{ $current['label'] }}».</div>
@else
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">الاسم</th>
                    <th class="px-4 py-3 font-medium">الهوية</th>
                    <th class="px-4 py-3 font-medium">المسؤول</th>
                    <th class="px-4 py-3 font-medium">تاريخ الزيارة</th>
                    <th class="px-4 py-3 font-medium">النقاط</th>
                    <th class="px-4 py-3 font-medium">المتبقي/فرد</th>
                    <th class="px-4 py-3 font-medium">التوصية</th>
                    <th class="px-4 py-3 font-medium">السياسة</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($assessments as $a)
                    @php $outdated = $active && $a->policy && $a->policy->version < $active->version; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $a->family->husband_name ?: ($a->family->wife_name ?: '—') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $a->family->husband_id ?: ($a->family->wife_id ?: '—') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $a->family->supervisor?->name ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $a->visit_date?->toDateString() ?: '—' }}</td>
                        <td class="px-4 py-3 font-bold text-indigo-700">{{ $a->total_score }}</td>
                        <td class="px-4 py-3">@money($a->per_person_remaining)</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $a->recommended ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $a->recommended ? 'مستحق' : 'غير مستحق' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            v{{ $a->policy?->version }}
                            @if ($outdated)<span class="text-amber-600 text-xs">(قديمة)</span>@endif
                        </td>
                        <td class="px-4 py-3 text-left whitespace-nowrap">
                            <a href="{{ route('assessments.show', $a) }}" class="text-indigo-600 hover:underline">عرض</a>
                            <a href="{{ route('assessments.edit', $a) }}" class="text-gray-500 hover:underline mr-2">تعديل</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
