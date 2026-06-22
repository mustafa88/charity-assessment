@extends('layouts.main')
@section('title', 'الزيارات القريبة')

@section('content')
<div class="flex items-center justify-between mb-1">
    <h2 class="text-xl font-semibold">الزيارات القريبة</h2>
    <span class="text-sm text-gray-400">{{ $visits->count() }} عائلة</span>
</div>
<p class="text-xs text-gray-400 mb-4">تاريخ الزيارة التالية لأحدث تقييم لكل عائلة — المتأخرة والأقرب أولاً.</p>

@if ($visits->isEmpty())
    <div class="bg-white rounded-lg p-8 text-center text-gray-400">لا توجد زيارات مجدولة بعد.</div>
@else
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">العائلة</th>
                    <th class="px-4 py-3 font-medium">الهوية</th>
                    <th class="px-4 py-3 font-medium">آخر زيارة</th>
                    <th class="px-4 py-3 font-medium">الزيارة التالية</th>
                    <th class="px-4 py-3 font-medium">الحالة</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($visits as $a)
                    @php
                        $days = (int) round(\Illuminate\Support\Carbon::today()->diffInDays($a->next_visit_date, false));
                        if ($days < 0)       { $label = 'متأخرة '.abs($days).' يوم'; $cls = 'bg-red-100 text-red-700'; $rowCls = 'bg-red-50/40'; }
                        elseif ($days === 0) { $label = 'مستحقة اليوم';               $cls = 'bg-red-100 text-red-700'; $rowCls = 'bg-red-50/40'; }
                        elseif ($days <= 30) { $label = 'خلال '.$days.' يوم';          $cls = 'bg-amber-100 text-amber-700'; $rowCls = ''; }
                        else                 { $label = 'خلال '.$days.' يوم';          $cls = 'bg-gray-100 text-gray-500'; $rowCls = ''; }
                    @endphp
                    <tr class="hover:bg-gray-50 {{ $rowCls }}">
                        <td class="px-4 py-3 font-medium">{{ $a->family->husband_name ?: ($a->family->wife_name ?: '—') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $a->family->husband_id ?: ($a->family->wife_id ?: '—') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $a->visit_date?->toDateString() ?: '—' }}</td>
                        <td class="px-4 py-3 font-medium">{{ $a->next_visit_date->toDateString() }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $cls }}">{{ $label }}</span>
                        </td>
                        <td class="px-4 py-3 text-left whitespace-nowrap">
                            <a href="{{ route('assessments.show', $a) }}" class="text-indigo-600 hover:underline">عرض</a>
                            <a href="{{ route('assessments.edit', $a) }}" class="text-indigo-600 hover:underline mr-2">زيارة جديدة</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
