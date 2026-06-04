@extends('layouts.app')

@section('title', 'Notes List')

@section('content')
    <section class="flex flex-col gap-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-teal-700">Library</p>
                <h2 class="text-2xl font-bold text-zinc-950">Notes list</h2>
            </div>

            <form method="GET" action="{{ route('notes.index') }}" class="flex w-full flex-col gap-2 sm:flex-row lg:max-w-xl" data-loading>
                <label for="q" class="sr-only">Semantic search</label>
                <input id="q" name="q" value="{{ $query }}" type="search" placeholder="Search notes by meaning" class="min-h-11 flex-1 rounded-md border border-zinc-300 bg-white px-3 text-sm outline-none ring-teal-600/20 transition focus:border-teal-700 focus:ring-4">
                <button type="submit" data-loading-text="Searching" class="min-h-11 rounded-md bg-teal-700 px-4 text-sm font-semibold text-white transition hover:bg-teal-800">Search</button>
                @if ($isSearch)
                    <a href="{{ route('notes.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-zinc-300 bg-white px-4 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100">Clear</a>
                @endif
            </form>
        </div>

        @if ($isSearch)
            <p class="text-sm text-zinc-500">{{ $notes->count() }} semantic matches for "{{ $query }}".</p>
        @else
            <p class="text-sm text-zinc-500">{{ $notes->total() }} notes saved.</p>
        @endif

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @forelse ($notes as $item)
                @php
                    $note = $isSearch ? $item['note'] : $item;
                    $score = $isSearch ? $item['score'] : null;
                @endphp

                <article class="flex min-h-64 flex-col gap-4 rounded-md border border-zinc-200 bg-white p-4 shadow-sm transition hover:border-teal-300">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-zinc-950">{{ $note->title }}</h3>
                            <p class="mt-1 text-xs font-medium uppercase text-zinc-500">{{ $note->updated_at->format('M d, Y h:i A') }}</p>
                        </div>

                        @if ($score !== null)
                            <span class="rounded-md bg-teal-50 px-2 py-1 text-xs font-semibold text-teal-800">{{ round($score * 100) }}% match</span>
                        @endif
                    </div>

                    <p class="line-clamp-5 whitespace-pre-line text-sm leading-6 text-zinc-700">{{ $note->content }}</p>

                    @if ($note->summary)
                        <div class="rounded-md bg-zinc-50 p-3 text-sm leading-6 text-zinc-700">{{ $note->summary }}</div>
                    @endif

                    <div class="mt-auto flex flex-wrap justify-end gap-2">
                        <a href="{{ route('notes.show', $note) }}" class="rounded-md border border-zinc-300 px-3 py-1.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100">View</a>
                        <a href="{{ route('notes.edit', $note) }}" class="rounded-md border border-zinc-300 px-3 py-1.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100">Edit</a>
                        <form method="POST" action="{{ route('notes.destroy', $note) }}" data-loading>
                            @csrf
                            @method('DELETE')
                            <button type="submit" data-loading-text="Deleting" class="rounded-md border border-red-200 px-3 py-1.5 text-sm font-medium text-red-700 transition hover:bg-red-50">Delete</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-md border border-dashed border-zinc-300 bg-white p-8 text-sm text-zinc-500">
                    No notes found. Create your first note to get started.
                </div>
            @endforelse
        </div>

        @if (! $isSearch)
            <div>
                {{ $notes->links() }}
            </div>
        @endif
    </section>
@endsection
