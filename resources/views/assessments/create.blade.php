@extends('layouts.main')
@section('title', 'تقييم جديد')

@section('content')
    @include('assessments.partials._form', ['policy' => $policy, 'supervisors' => $supervisors])
@endsection
