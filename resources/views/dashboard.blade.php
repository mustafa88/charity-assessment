@extends('layouts.main')

@section('title', 'الرئيسية')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">مرحباً، {{ auth()->user()->name }}</h1>
        <p class="text-gray-500 mt-1">اختر القسم الذي تريد العمل عليه.</p>
    </div>

    {{--
        بطاقات الأقسام. لإضافة قسم جديد مستقبلاً: انسخ بطاقة وغيّر
        الرابط (route) والعنوان والوصف والأيقونة واللون. لا منطق هنا.
    --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

        {{-- العائلات المقبولة --}}
        <a href="{{ route('assessments.index', ['status' => 'accepted']) }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-green-200">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-green-50 text-green-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 group-hover:text-green-700">العائلات المقبولة</h3>
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">{{ $byStatus['accepted'] }}</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">العائلات المقبولة بالقرار اليدوي.</p>
            </div>
        </a>

        {{-- قيد الانتظار --}}
        <a href="{{ route('assessments.index', ['status' => 'pending']) }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-amber-200">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 group-hover:text-amber-700">قيد الانتظار</h3>
                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">{{ $byStatus['pending'] }}</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">تقييمات تنتظر قراراً يدوياً.</p>
            </div>
        </a>

        {{-- مرفوضة (أقل أهمية — متاحة عند الحاجة) --}}
        <a href="{{ route('assessments.index', ['status' => 'rejected']) }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-gray-300">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l4-4m0 4l-4-4m11 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 group-hover:text-gray-700">العائلات المرفوضة</h3>
                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ $byStatus['rejected'] }}</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">سجلّ المرفوضة — للرجوع عند الحاجة.</p>
            </div>
        </a>

        {{-- تقييم جديد --}}
        <a href="{{ route('assessments.create') }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-green-200">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-green-50 text-green-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-800 group-hover:text-green-700">تقييم جديد</h3>
                <p class="text-sm text-gray-500 mt-1">إنشاء استمارة تقييم لعائلة جديدة.</p>
            </div>
        </a>

        {{-- الزيارات القريبة --}}
        <a href="{{ route('visits.upcoming') }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-amber-200">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 group-hover:text-amber-700">الزيارات القريبة</h3>
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $upcomingCount }}</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">العائلات المستحقّة لزيارة متابعة قريبة.</p>
            </div>
        </a>

        {{-- مراجعة الأيتام --}}
        <a href="{{ route('orphans.index') }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-rose-200">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 group-hover:text-rose-700">مراجعة الأيتام</h3>
                    @if ($orphanReviewCount > 0)
                        <span class="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full">{{ $orphanReviewCount }}</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-1">من بلغ 15 سنة ويحتاج موافقة على الإخراج.</p>
            </div>
        </a>

        {{-- الإحصائيات --}}
        <a href="{{ route('statistics.index') }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-sky-200">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-800 group-hover:text-sky-700">الإحصائيات</h3>
                <p class="text-sm text-gray-500 mt-1">أرقام العائلات المقبولة: الأيتام، الأولاد، الترميم، التوزيعات.</p>
            </div>
        </a>

        {{-- جميع الأيتام --}}
        <a href="{{ route('orphans.all') }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-rose-200">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-800 group-hover:text-rose-700">جميع الأيتام</h3>
                <p class="text-sm text-gray-500 mt-1">قائمة الأيتام في العائلات المقبولة — مع تصدير PDF.</p>
            </div>
        </a>

        {{-- تصفّح العائلات --}}
        <a href="{{ route('families.browse', ['filter' => 'orphans']) }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-indigo-200">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L14 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 018 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-800 group-hover:text-indigo-700">تصفّح العائلات</h3>
                <p class="text-sm text-gray-500 mt-1">العائلات المقبولة حسب: الأيتام، الترميم، الحالة الاجتماعية.</p>
            </div>
        </a>

        {{-- تصفّح الأفراد --}}
        <a href="{{ route('members.browse', ['filter' => 'children']) }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-sky-200">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-800 group-hover:text-sky-700">تصفّح الأفراد</h3>
                <p class="text-sm text-gray-500 mt-1">أفراد العائلات المقبولة: كل الأولاد أو الأيتام فقط.</p>
            </div>
        </a>

        {{-- مقبولة بلا مسؤول --}}
        <a href="{{ route('families.unassigned') }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-orange-200">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-3a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 group-hover:text-orange-700">مقبولة بلا مسؤول</h3>
                    @if ($unassignedCount > 0)
                        <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">{{ $unassignedCount }}</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-1">عائلات مقبولة تحتاج تحديد مسؤول.</p>
            </div>
        </a>

        {{-- المسؤولون --}}
        <a href="{{ route('supervisors.index') }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-teal-200">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-3-1.5"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 group-hover:text-teal-700">المسؤولون</h3>
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $supervisorsCount }}</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">إدارة المسؤولين عن العائلات.</p>
            </div>
        </a>

        {{-- سياسة النقاط --}}
        <a href="{{ route('policies.index') }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-sky-200">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l9-3 9 3-9 3-9-3zm0 6l9 3 9-3M3 18l9 3 9-3"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 group-hover:text-sky-700">سياسة النقاط</h3>
                    @if ($active)
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">v{{ $active->version }}</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-1">عتبة الاستحقاق وأوزان النقاط وإصداراتها.</p>
            </div>
        </a>

        {{-- الملف الشخصي --}}
        <a href="{{ route('profile.edit') }}"
           class="group bg-white rounded-xl shadow-sm hover:shadow-md transition p-5 flex items-start gap-4 border border-transparent hover:border-gray-300">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.12 17.8A7 7 0 0112 15a7 7 0 016.88 2.8M15 9a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-gray-800 group-hover:text-gray-900">الملف الشخصي</h3>
                <p class="text-sm text-gray-500 mt-1">تعديل بيانات الحساب وكلمة المرور.</p>
            </div>
        </a>

    </div>
@endsection
