@extends('layouts.main')
@section('title', 'الإحصائيات')

@php
    $maritalLabels = ['married' => 'متزوج', 'divorced' => 'مطلّق', 'widowed' => 'أرمل', 'abandoned' => 'تارك للبيت'];
    $houseLabels   = ['own' => 'ملك', 'rent' => 'إيجار', 'family' => 'عائلي', 'other' => 'أخرى'];
@endphp

@section('content')
<div class="flex items-center justify-between mb-1">
    <h2 class="text-xl font-semibold">الإحصائيات</h2>
</div>
<p class="text-xs text-gray-400 mb-5">أرقام محسوبة على <span class="text-gray-500">العائلات المقبولة فقط</span> (أحدث تقييم قراره «مقبول»).</p>

{{-- البطاقات الرئيسية --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-3xl font-bold text-indigo-700">{{ $stats['families'] }}</div>
        <div class="text-sm text-gray-500 mt-1">عدد العائلات المقبولة</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-3xl font-bold text-rose-600">{{ $stats['orphan_families'] }}</div>
        <div class="text-sm text-gray-500 mt-1">عدد عائلات الأيتام</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-3xl font-bold text-amber-600">{{ $stats['needs_repair'] }}</div>
        <div class="text-sm text-gray-500 mt-1">بيوت بحاجة إلى ترميم</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-3xl font-bold text-gray-700">{{ $stats['children']['total'] }}</div>
        <div class="text-sm text-gray-500 mt-1">إجمالي الأولاد</div>
    </div>
</div>

{{-- الأولاد والأيتام (التفصيل حسب الجنس) --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-4">الأولاد في جميع العائلات</h3>
        <div class="grid grid-cols-3 gap-3 text-center">
            <div class="rounded-lg bg-gray-50 p-3">
                <div class="text-2xl font-bold text-gray-700">{{ $stats['children']['total'] }}</div>
                <div class="text-xs text-gray-500 mt-1">الإجمالي</div>
            </div>
            <div class="rounded-lg bg-sky-50 p-3">
                <div class="text-2xl font-bold text-sky-700">{{ $stats['children']['m'] }}</div>
                <div class="text-xs text-gray-500 mt-1">ذكور</div>
            </div>
            <div class="rounded-lg bg-pink-50 p-3">
                <div class="text-2xl font-bold text-pink-600">{{ $stats['children']['f'] }}</div>
                <div class="text-xs text-gray-500 mt-1">إناث</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-rose-700 mb-4">الأولاد الأيتام</h3>
        <div class="grid grid-cols-3 gap-3 text-center">
            <div class="rounded-lg bg-gray-50 p-3">
                <div class="text-2xl font-bold text-gray-700">{{ $stats['orphans']['total'] }}</div>
                <div class="text-xs text-gray-500 mt-1">الإجمالي</div>
            </div>
            <div class="rounded-lg bg-sky-50 p-3">
                <div class="text-2xl font-bold text-sky-700">{{ $stats['orphans']['m'] }}</div>
                <div class="text-xs text-gray-500 mt-1">ذكور</div>
            </div>
            <div class="rounded-lg bg-pink-50 p-3">
                <div class="text-2xl font-bold text-pink-600">{{ $stats['orphans']['f'] }}</div>
                <div class="text-xs text-gray-500 mt-1">إناث</div>
            </div>
        </div>
    </div>
</div>

{{-- التوزيعات --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-4">العائلات حسب الحالة الاجتماعية</h3>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach ($maritalLabels as $key => $label)
                    <tr>
                        <td class="py-2 text-gray-600">{{ $label }}</td>
                        <td class="py-2 text-left font-bold text-indigo-700">{{ $stats['marital'][$key] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-indigo-700 mb-4">العائلات حسب نوع السكن</h3>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach ($houseLabels as $key => $label)
                    <tr>
                        <td class="py-2 text-gray-600">{{ $label }}</td>
                        <td class="py-2 text-left font-bold text-indigo-700">{{ $stats['house'][$key] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
