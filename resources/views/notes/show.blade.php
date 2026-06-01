@extends('layouts.app')

@section('title', $note->title)

@section('content')
    <article class="mx-auto max-w-4xl rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 pb-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-teal-700">View</p>
                <h2 class="mt-1 text-2xl font-bold text-zinc-950">{{ $note->title }}</h2>
                <p class="mt-2 text-sm text-zinc-500">Updated {{ $note->updated_at->format('M d, Y h:i A') }}</p>
            </div>

            <div class="flex flex-wrap justify-start gap-2 lg:justify-end">
                <a href="{{ route('notes.index') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100">Back to list</a>
                <a href="{{ route('notes.edit', $note) }}" class="rounded-md bg-zinc-950 px-3 py-2 text-sm font-semibold text-white transition hover:bg-zinc-800">Edit note</a>
            </div>
        </div>

        <div class="mt-5 whitespace-pre-line text-base leading-7 text-zinc-800">{{ $note->content }}</div>

        <section class="mt-6 rounded-md bg-zinc-50 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-base font-semibold text-zinc-950">AI summary</h3>
                <form method="POST" action="{{ route('notes.summary', $note) }}">
                    @csrf
                    <button type="submit" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100">Generate summary</button>
                </form>
            </div>
            <p class="mt-3 text-sm leading-6 text-zinc-700">{{ $note->summary ?: 'No summary generated yet.' }}</p>
        </section>

        <form method="POST" action="{{ route('notes.destroy', $note) }}" class="mt-6 flex justify-end">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">Delete note</button>
        </form>
    </article>
@endsection
