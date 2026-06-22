@extends('layouts.main')
@section('title', 'تعديل تقييم')

@section('content')
    @include('assessments.partials._form', ['a' => $a, 'policy' => $policy, 'supervisors' => $supervisors])
@endsection
