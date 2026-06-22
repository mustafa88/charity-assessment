@extends('layouts.main')
@section('title', 'سياسة النقاط')

@php
    $bands = old('bands', $active?->bands ?? [['max' => 500, 'points' => 3], ['max' => 1000, 'points' => 2], ['max' => null, 'points' => 1]]);
    $arch  = old('arch_points', $active?->arch_points ?? [0, 1, 2, 3]);
    $val   = fn ($key, $default) => old($key, $active->{$key} ?? $default);
@endphp

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- نموذج إصدار جديد --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold">سياسة النقاط</h2>
            <span class="text-xs text-gray-400">المعتمدة حالياً: v{{ $active?->version ?? '—' }}</span>
        </div>
        <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg px-4 py-3">
            أي حفظ يُنشئ <b>إصداراً جديداً</b> ويصبح هو المعتمد. لا يُعدَّل إصدار قائم، والتقييمات السابقة تبقى محسوبة بإصدارها.
        </div>

        <form method="POST" action="{{ route('policies.store') }}"
              onsubmit="return confirm('سيُنشأ إصدار جديد من السياسة ويصبح هو المعتمد. التقييمات القديمة لا تتأثر. متابعة؟')"
              class="bg-white rounded-lg shadow-sm p-5 space-y-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs text-gray-500 mb-1">عتبة الاستحقاق (₪/فرد)</label>
                    <input type="number" step="any" name="approval_threshold" value="{{ $val('approval_threshold', 1200) }}" class="input"></div>
                <div><label class="block text-xs text-gray-500 mb-1">نقاط الفرد المستحق</label>
                    <input type="number" name="per_eligible_person" value="{{ $val('per_eligible_person', 1) }}" class="input"></div>
                <div><label class="block text-xs text-gray-500 mb-1">مكافأة الإيجار</label>
                    <input type="number" name="rent_bonus" value="{{ $val('rent_bonus', 1) }}" class="input"></div>
                <div><label class="block text-xs text-gray-500 mb-1">مكافأة الحالة الاجتماعية</label>
                    <input type="number" name="marital_bonus" value="{{ $val('marital_bonus', 1) }}" class="input"></div>
            </div>

            {{-- شرائح المتبقي للفرد --}}
            <div>
                <h4 class="text-sm font-medium text-indigo-700 mb-2">شرائح المتبقي للفرد</h4>
                <div class="space-y-2">
                    @foreach ($bands as $i => $b)
                        @php $isLast = $i === count($bands) - 1; @endphp
                        <div class="flex items-center gap-3 text-sm">
                            <span class="text-gray-500 w-28">{{ $isLast ? 'ما فوق ذلك' : 'أقل من' }}</span>
                            @if ($isLast)
                                <span class="text-gray-300 w-32">—</span>
                            @else
                                <input type="number" name="bands[{{ $i }}][max]" value="{{ $b['max'] }}" class="input w-32" placeholder="الحد">
                            @endif
                            <span class="text-gray-400">←</span>
                            <input type="number" name="bands[{{ $i }}][points]" value="{{ $b['points'] }}" class="input w-24" placeholder="نقاط">
                            <span class="text-gray-400 text-xs">نقطة</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- النواقص --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs text-gray-500 mb-1">حجم مجموعة النواقص</label>
                    <input type="number" name="missing_group_size" value="{{ $val('missing_group_size', 3) }}" class="input"></div>
                <div><label class="block text-xs text-gray-500 mb-1">نقاط كل مجموعة نواقص</label>
                    <input type="number" name="missing_group_points" value="{{ $val('missing_group_points', 1) }}" class="input"></div>
            </div>

            {{-- الحالة المعمارية --}}
            <div>
                <h4 class="text-sm font-medium text-indigo-700 mb-2">نقاط الحالة المعمارية</h4>
                <div class="grid grid-cols-4 gap-3">
                    @foreach (['ممتاز', 'جيد', 'سيئ', 'لا يصلح'] as $i => $label)
                        <div><label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                            <input type="number" name="arch_points[{{ $i }}]" value="{{ $arch[$i] ?? 0 }}" class="input"></div>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg font-medium">حفظ كإصدار جديد</button>
            </div>
        </form>
    </div>

    {{-- سجل الإصدارات --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold text-indigo-700 mb-3">سجل الإصدارات</h3>
            @if ($policies->isEmpty())
                <p class="text-sm text-gray-400">لا إصدارات بعد.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($policies as $p)
                        <li class="flex items-center justify-between border-b border-gray-50 pb-2">
                            <div>
                                <span class="font-medium">v{{ $p->version }}</span>
                                <span class="text-gray-400 text-xs mr-2">عتبة {{ $p->approval_threshold }}₪</span>
                            </div>
                            @if ($p->is_active)
                                <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs">معتمدة</span>
                            @else
                                <span class="text-gray-300 text-xs">{{ optional($p->effective_from)->toDateString() }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
