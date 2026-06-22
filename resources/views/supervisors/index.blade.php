@extends('layouts.main')
@section('title', 'المسؤولون عن العائلات')

@section('content')
<div class="max-w-3xl">
    <h2 class="text-xl font-semibold mb-4">المسؤولون عن العائلات</h2>

    {{-- نموذج إضافة --}}
    <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
        <h3 class="font-semibold text-indigo-700 mb-3">إضافة مسؤول</h3>
        <form method="POST" action="{{ route('supervisors.store') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs text-gray-500 mb-1">الاسم</label>
                <input name="name" value="{{ old('name') }}" class="input" placeholder="اسم المسؤول">
            </div>
            <div class="w-44">
                <label class="block text-xs text-gray-500 mb-1">الهاتف (اختياري)</label>
                <input name="phone" value="{{ old('phone') }}" class="input">
            </div>
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-medium">إضافة</button>
        </form>
    </div>

    {{-- القائمة --}}
    @if ($supervisors->isEmpty())
        <div class="bg-white rounded-lg p-8 text-center text-gray-400">لا يوجد مسؤولون بعد. أضِف أول واحد.</div>
    @else
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-right">
                    <tr>
                        <th class="px-4 py-3 font-medium">الاسم</th>
                        <th class="px-4 py-3 font-medium">الهاتف</th>
                        <th class="px-4 py-3 font-medium">عدد العائلات</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($supervisors as $sup)
                        <tr x-data="{ edit: false }">
                            {{-- وضع العرض --}}
                            <td x-show="!edit" class="px-4 py-3 font-medium">{{ $sup->name }}</td>
                            <td x-show="!edit" class="px-4 py-3 text-gray-500">{{ $sup->phone ?: '—' }}</td>
                            <td x-show="!edit" class="px-4 py-3 text-gray-500">{{ $sup->families_count }}</td>
                            <td x-show="!edit" class="px-4 py-3 text-left whitespace-nowrap">
                                <button @click="edit = true" class="text-indigo-600 hover:underline">تعديل</button>
                                @if ($sup->families_count > 0)
                                    {{-- يُمنع الحذف لمن لديه عائلات مرتبطة --}}
                                    <span class="text-gray-300 mr-2 cursor-not-allowed" title="لا يمكن الحذف: مسؤول عن {{ $sup->families_count }} عائلة. انقل عائلاته أولاً.">حذف</span>
                                @else
                                    <form method="POST" action="{{ route('supervisors.destroy', $sup) }}" class="inline mr-2"
                                          onsubmit="return confirm('حذف «{{ $sup->name }}»؟')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 hover:underline">حذف</button>
                                    </form>
                                @endif
                            </td>

                            {{-- وضع التعديل (PUT) --}}
                            <td x-show="edit" colspan="4" class="px-4 py-3" x-cloak>
                                <form method="POST" action="{{ route('supervisors.update', $sup) }}" class="flex flex-wrap items-end gap-3">
                                    @csrf @method('PUT')
                                    <div class="flex-1 min-w-[160px]">
                                        <label class="block text-xs text-gray-500 mb-1">الاسم</label>
                                        <input name="name" value="{{ $sup->name }}" class="input">
                                    </div>
                                    <div class="w-44">
                                        <label class="block text-xs text-gray-500 mb-1">الهاتف</label>
                                        <input name="phone" value="{{ $sup->phone }}" class="input">
                                    </div>
                                    <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm">حفظ</button>
                                    <button type="button" @click="edit = false" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-600 text-sm">إلغاء</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
