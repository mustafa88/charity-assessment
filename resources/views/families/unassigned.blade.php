@extends('layouts.main')
@section('title', 'مقبولة بلا مسؤول')

@section('content')
<div class="flex items-center justify-between mb-1">
    <h2 class="text-xl font-semibold">عائلات مقبولة بلا مسؤول</h2>
    <span class="text-sm text-gray-400">{{ $families->count() }} عائلة</span>
</div>
<p class="text-xs text-gray-400 mb-4">عائلات مقبولة بالقرار اليدوي ولم يُحدَّد لها مسؤول بعد — حدّد المسؤول مباشرة من هنا.</p>

@if ($families->isEmpty())
    <div class="bg-white rounded-lg p-8 text-center text-gray-400">كل العائلات المقبولة لها مسؤول. 👍</div>
@else
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">العائلة</th>
                    <th class="px-4 py-3 font-medium">الهوية</th>
                    <th class="px-4 py-3 font-medium">تحديد المسؤول</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($families as $f)
                    @php $latest = $f->assessments->first(); @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $f->husband_name ?: ($f->wife_name ?: '—') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $f->husband_id ?: ($f->wife_id ?: '—') }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('families.assignSupervisor', $f) }}" class="flex items-center gap-2">
                                @csrf
                                <select name="supervisor_id" class="input w-56">
                                    <option value="">— اختر مسؤولاً —</option>
                                    @foreach ($supervisors as $sup)
                                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                    @endforeach
                                </select>
                                <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">حفظ</button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-left whitespace-nowrap">
                            @if ($latest)
                                <a href="{{ route('assessments.show', $latest) }}" class="text-indigo-600 hover:underline">عرض التقييم</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($supervisors->isEmpty())
        <p class="text-xs text-amber-600 mt-3">لا يوجد مسؤولون بعد — <a href="{{ route('supervisors.index') }}" class="underline">أضِف مسؤولين أولاً</a>.</p>
    @endif
@endif
@endsection
