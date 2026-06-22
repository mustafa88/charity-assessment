<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'منظومة تقييم العائلات') — مؤسسة عطاء</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-100 text-gray-900 antialiased min-h-screen">

@php
    // السياسة المعتمدة — تُعرض في الشارة العلوية (قد لا توجد قبل تشغيل الـ seeder)
    $activePolicy = \App\Models\ScoringPolicy::where('is_active', true)->latest('version')->first();
@endphp

<!-- شريط علوي -->
<header class="bg-white shadow-sm">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-lg font-bold text-indigo-700">منظومة تقييم العائلات</a>
            <span class="text-xs text-gray-400">مؤسسة عطاء</span>
            @if ($activePolicy)
                <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full">
                    السياسة v{{ $activePolicy->version }} · عتبة {{ $activePolicy->approval_threshold }}₪
                </span>
            @endif
        </div>
        <nav class="flex items-center gap-3 text-sm">
            <a href="{{ route('dashboard') }}"
               class="{{ request()->routeIs('dashboard') ? 'text-indigo-700 font-medium' : 'text-gray-500 hover:underline' }}">الرئيسية</a>
            <span class="text-gray-300">|</span>
            <span class="text-gray-500">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="text-red-600 hover:underline">خروج</button>
            </form>
        </nav>
    </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-6">

    {{-- رسالة نجاح --}}
    @if (session('status'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded">{{ session('status') }}</div>
    @endif

    {{-- رسالة منع/خطأ --}}
    @if (session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded">{{ session('error') }}</div>
    @endif

    {{-- أخطاء التحقق --}}
    @if ($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <p class="font-medium mb-1">تحقّق من الحقول التالية:</p>
            <ul class="list-disc pr-5 text-sm space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

@stack('scripts')
</body>
</html>
