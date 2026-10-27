@extends('layouts.sprout')

@section('content')
<section class="grid md:grid-cols-2 gap-8 items-center py-8">
    <div>
        <h1 class="font-display font-extrabold text-4xl md:text-5xl leading-tight">Grow a plant pet. Then meet it in real life.</h1>
        <p class="mt-4 text-lg max-w-prose">Choose a plant, keep it watered and fed, dress it up, and watch it grow from a seed into a tree. On your phone, bring it into the world through your camera with web AR.</p>
        <div class="mt-6 flex gap-3 flex-wrap">
            <a href="{{ route('start') }}" class="bg-leaf text-white font-semibold rounded px-6 py-3 hover:bg-leaf/90">Start</a>
            @guest <a href="{{ route('register') }}" class="border border-forest rounded px-6 py-3 font-semibold">Create Account</a> @endguest
        </div>
        <p class="mt-3 text-sm text-soil">The AR camera works on phones. On a computer you can still care for your garden and browse the community map.</p>
    </div>

    <ol class="rounded-2xl bg-forest text-paper p-6 space-y-3 list-decimal ml-0 pl-10">
        <li><strong>Choose</strong> a plant and give it a name.</li>
        <li><strong>Care</strong> for it: water, feed and decorate.</li>
        <li><strong>See it in AR</strong> on your phone and play with it.</li>
        <li><strong>Watch it grow</strong> from seed to mature tree.</li>
    </ol>
</section>
@endsection
