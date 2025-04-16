@extends('layouts.master')
@section('title', 'Email Verification')
@section('content')
<div class="row">
    <div class="col-sm-6 m-4">
        <div class="alert alert-success">
            <strong>Congratulations!</strong>
            <p>Dear {{ $user->name }}, your email {{ $user->email }} is verified.</p>
            <p>You can now <a href="{{ route('login') }}">login</a> to your account.</p>
        </div>
    </div>
</div>
@endsection 