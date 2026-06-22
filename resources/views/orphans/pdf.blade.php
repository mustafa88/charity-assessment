@php
    $genderLabels = ['m' => 'ولد', 'f' => 'بنت'];
    $dash = fn ($v) => ($v === null || $v === '') ? '—' : $v;
@endphp
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<style>
    * { font-family: xbriyaz, sans-serif; }
    body { font-size: 11px; color: #1f2937; }
    .head { border-bottom: 2px solid #4f46e5; padding-bottom: 6px; margin-bottom: 10px; }
    .head .org { color: #4f46e5; font-size: 16px; font-weight: bold; }
    .head .sub { color: #6b7280; font-size: 10px; margin-top: 2px; }

    table { width: 100%; border-collapse: collapse; }
    .data th { background: #eef2ff; color: #3730a3; text-align: right; padding: 6px; font-size: 11px; border: 1px solid #c7d2fe; }
    .data td { padding: 5px 6px; border: 1px solid #e5e7eb; font-size: 10px; }
    .data tr:nth-child(even) td { background: #f9fafb; }
    .muted { color: #9ca3af; }
    .num { color: #6b7280; }
</style>
</head>
<body>
    <div class="head">
        <div class="org">مبرة عطاء — قائمة الأيتام</div>
        <div class="sub">الأيتام ضمن العائلات المقبولة · العدد: {{ $orphans->count() }}</div>
    </div>

    @if ($orphans->isEmpty())
        <p class="muted">لا يوجد أيتام ضمن العائلات المقبولة.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width:4%">#</th>
                    <th style="width:22%">الاسم</th>
                    <th style="width:8%">العمر</th>
                    <th style="width:9%">ولد/بنت</th>
                    <th style="width:20%">اسم الأم</th>
                    <th style="width:15%">الهاتف</th>
                    <th style="width:22%">المسؤول عن العائلة</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orphans as $i => $o)
                    <tr>
                        <td class="num">{{ $i + 1 }}</td>
                        <td>{{ $dash($o->name) }}</td>
                        <td>{{ $o->age !== null ? $o->age . ' سنة' : '—' }}</td>
                        <td>{{ $genderLabels[$o->gender] ?? '—' }}</td>
                        <td>{{ $dash($o->mother) }}</td>
                        <td>{{ $dash($o->phone) }}</td>
                        <td>{{ $o->supervisor ?: '— بلا مسؤول —' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
