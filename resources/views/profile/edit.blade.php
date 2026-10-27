@extends('layouts.sprout')
@section('title', 'Profile')

@section('content')
<div class="mb-6">
    <h1 class="font-display font-extrabold text-3xl">Profile</h1>
    <p class="text-soil mt-1">Manage your account details and security.</p>
</div>

<div class="max-w-3xl space-y-5">
    <section class="rounded-xl bg-white p-5 sm:p-8">
        @include('profile.partials.update-profile-information-form')
    </section>

    <section class="rounded-xl bg-white p-5 sm:p-8">
        @include('profile.partials.update-password-form')
    </section>

    <section class="rounded-xl bg-white p-5 sm:p-8">
        @include('profile.partials.delete-user-form')
    </section>
</div>
@endsection
