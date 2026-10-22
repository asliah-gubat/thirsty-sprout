@extends('layouts.sprout')
@section('title', 'Choose a new password')
@section('inline-errors', '1')

@section('content')
@php
    $input = 'w-full rounded-lg border border-[#CFD6CA] bg-[#F8FAF5] px-3.5 py-3 text-base text-forest outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/25';
@endphp

<div class="mx-auto mt-14 mb-10 w-full max-w-[480px] text-center">
    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-leaf">Fresh start</p>
    <h1 class="mt-2 font-display text-3xl font-extrabold">Choose a new password</h1>
    <p class="mb-7 mt-2.5 text-soil">Set a new password to get back to your garden.</p>

    <form method="POST" action="{{ route('password.store') }}"
        class="space-y-5 rounded-[20px] border border-forest/10 bg-white px-9 pb-9 pt-9 text-left shadow-sm">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" class="{{ $input }}">
            @error('email')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-semibold">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="{{ $input }}">
            @error('password')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="{{ $input }}">
            @error('password_confirmation')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <button class="w-full rounded-xl bg-leaf py-3.5 text-base font-semibold text-white hover:bg-leaf/90">Reset password</button>
    </form>
</div>
@endsection
