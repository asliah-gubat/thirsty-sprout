@extends('layouts.sprout')
@section('title', 'Forgot your password?')
@section('inline-errors', '1')
@section('inline-status', '1')

@section('content')
@php
    $input = 'w-full rounded-lg border border-[#CFD6CA] bg-[#F8FAF5] px-3.5 py-3 text-base text-forest outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/25';
@endphp

<div class="mx-auto mt-14 mb-10 w-full max-w-[480px] text-center">
    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-leaf">Need a hand?</p>
    <h1 class="mt-2 font-display text-3xl font-extrabold">Reset your password</h1>
    <p class="mb-7 mt-2.5 text-soil">Enter your email and we’ll send you a link to choose a new password.</p>

    <form method="POST" action="{{ route('password.email') }}"
        class="space-y-5 rounded-[20px] border border-forest/10 bg-white px-9 pb-9 pt-9 text-left shadow-sm">
        @csrf

        @if (session('status'))
            <p role="status" class="rounded-lg bg-leaf/10 px-3.5 py-3 text-sm text-forest">{{ session('status') }}</p>
        @endif

        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="{{ $input }}">
            @error('email')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <button class="w-full rounded-xl bg-leaf py-3.5 text-base font-semibold text-white hover:bg-leaf/90">Email password reset link</button>
    </form>

    <p class="mt-6 text-[15px] text-soil">Remembered your password?
        <a href="{{ route('login') }}" class="font-semibold text-leaf hover:underline">Log In</a>
    </p>
</div>
@endsection
