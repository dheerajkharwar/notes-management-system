@extends('layouts.app')

@section('title', 'Create Note')

@section('content')
    <section class="mx-auto max-w-3xl">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-teal-700">Create</p>
                <h2 class="text-2xl font-bold text-zinc-950">Create note</h2>
            </div>
            <a href="{{ route('notes.index') }}" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100">Back to list</a>
        </div>

        <form method="POST" action="{{ route('notes.store') }}" class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
            @include('notes._form')
        </form>
    </section>
@endsection
