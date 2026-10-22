@extends('layouts.sprout')
@section('title', 'Log in')
@section('inline-errors', '1')

@section('content')
@php
    $input = 'w-full rounded-lg border border-[#CFD6CA] bg-[#F8FAF5] px-3.5 py-3 text-base text-forest outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/25';
@endphp

<div class="mx-auto mt-14 mb-10 w-full max-w-[480px] text-center">
    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-leaf">Welcome back</p>
    <h1 class="mt-2 font-display text-3xl font-extrabold">Log in to your garden</h1>
    <p class="mb-7 mt-2.5 text-soil">Your plants are ready for a little care.</p>

    <form method="POST" action="{{ route('login') }}"
        class="space-y-5 rounded-[20px] border border-forest/10 bg-white px-9 pb-9 pt-14 text-left shadow-sm">
        @csrf

        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="{{ $input }}">
            @error('email')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-semibold">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="{{ $input }}">
            @error('password')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 text-[15px] text-soil">
                <input id="remember_me" type="checkbox" name="remember" class="accent-[#3F8F4A]">
                Remember me
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-[15px] font-semibold text-leaf hover:underline">Forgot your password?</a>
            @endif
        </div>

        <button class="w-full rounded-xl bg-leaf py-3.5 text-base font-semibold text-white hover:bg-leaf/90">Log in</button>
    </form>

    <p class="mt-6 text-[15px] text-soil">New to Thirsty Sprout?
        <a href="{{ route('register') }}" class="font-semibold text-leaf hover:underline">Create an account</a>
    </p>
</div>
@endsection