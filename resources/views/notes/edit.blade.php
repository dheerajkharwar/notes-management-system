@extends('layouts.app')

@section('title', 'Edit Note')

@section('content')
    <section class="mx-auto max-w-3xl">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-teal-700">Edit</p>
                <h2 class="text-2xl font-bold text-zinc-950">Edit note</h2>
            </div>
            <a href="{{ route('notes.show', $note) }}" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100">View note</a>
        </div>

        <form method="POST" action="{{ route('notes.update', $note) }}" class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm" data-loading>
            @include('notes._form', ['note' => $note])
        </form>
    </section>
@endsection
