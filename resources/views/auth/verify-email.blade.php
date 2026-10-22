@extends('layouts.sprout')
@section('title', 'Verify your email')
@section('inline-status', '1')

@section('content')
<div class="mx-auto mt-14 mb-10 w-full max-w-[480px] text-center">
    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-leaf">Almost there</p>
    <h1 class="mt-2 font-display text-3xl font-extrabold">Verify your email</h1>
    <p class="mb-7 mt-2.5 text-soil">Check your inbox for a verification link so you can get growing.</p>

    <div class="space-y-5 rounded-[20px] border border-forest/10 bg-white px-9 py-9 text-left shadow-sm">
        <p class="text-sm leading-6 text-forest">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <p role="status" class="rounded-lg bg-leaf/10 px-3.5 py-3 text-sm text-forest">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </p>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="w-full rounded-xl bg-leaf py-3.5 text-base font-semibold text-white hover:bg-leaf/90">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-xl border border-forest/15 py-3 text-sm font-semibold text-forest hover:bg-paper">Log out</button>
        </form>
    </div>
</div>
@endsection
