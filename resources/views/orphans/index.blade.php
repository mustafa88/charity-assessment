@extends('layouts.main')
@section('title', 'مراجعة الأيتام')

@section('content')
<div class="flex items-center justify-between mb-1">
    <h2 class="text-xl font-semibold">مراجعة الأيتام</h2>
    <span class="text-sm text-gray-400">{{ $reviews->count() }} للمراجعة</span>
</div>
<p class="text-xs text-gray-400 mb-4">
    الأفراد المُحدَّدون «يتيم» وبلغوا 15 سنة فأكثر، ضمن <span class="text-gray-500">العائلات المقبولة فقط</span> (أحدث تقييم قراره «مقبول»).
    إخراج الفرد من الأيتام لا يتمّ آلياً — يحتاج موافقتك أدناه.
</p>

@if ($reviews->isEmpty())
    <div class="bg-white rounded-lg p-8 text-center text-gray-400">لا يوجد من يحتاج مراجعة حالياً.</div>
@else
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">الفرد</th>
                    <th class="px-4 py-3 font-medium">العمر</th>
                    <th class="px-4 py-3 font-medium">العائلة</th>
                    <th class="px-4 py-3 font-medium">آخر زيارة</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($reviews as $rv)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $rv->member->name ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-xs">{{ $rv->age }} سنة</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $rv->family->husband_name ?: ($rv->family->wife_name ?: ('عائلة #'.$rv->family->id)) }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $rv->assessment->visit_date?->toDateString() ?: '—' }}</td>
                        <td class="px-4 py-3 text-left whitespace-nowrap">
                            <a href="{{ route('assessments.show', $rv->assessment) }}" class="text-indigo-600 hover:underline">عرض</a>
                            <form method="POST" action="{{ route('members.removeOrphan', $rv->member) }}" class="inline mr-2"
                                  onsubmit="return confirm('تأكيد: «{{ $rv->member->name }}» بلغ {{ $rv->age }} سنة — الموافقة على إخراجه من الأيتام؟')">
                                @csrf
                                <button class="border border-purple-400 text-purple-700 px-3 py-1.5 rounded-lg text-xs hover:bg-purple-50">
                                    الموافقة على الإخراج من الأيتام
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
