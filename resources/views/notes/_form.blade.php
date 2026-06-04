@csrf

@isset($note)
    @method('PUT')
@endisset

<div class="flex flex-col gap-5">
    <label class="flex flex-col gap-1 text-sm font-medium text-zinc-700">
        Title
        <input
            name="title"
            value="{{ old('title', $note->title ?? '') }}"
            required
            maxlength="180"
            class="min-h-11 rounded-md border border-zinc-300 bg-white px-3 text-sm font-normal text-zinc-950 outline-none ring-teal-600/20 transition focus:border-teal-700 focus:ring-4"
        >
        @error('title')
            <span class="text-sm font-normal text-red-700">{{ $message }}</span>
        @enderror
    </label>

    <label class="flex flex-col gap-1 text-sm font-medium text-zinc-700">
        Content
        <textarea
            name="content"
            required
            rows="14"
            class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-normal leading-6 text-zinc-950 outline-none ring-teal-600/20 transition focus:border-teal-700 focus:ring-4"
        >{{ old('content', $note->content ?? '') }}</textarea>
        @error('content')
            <span class="text-sm font-normal text-red-700">{{ $message }}</span>
        @enderror
    </label>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ isset($note) ? route('notes.show', $note) : route('notes.index') }}" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100">Cancel</a>
        <button type="submit" data-loading-text="{{ isset($note) ? 'Updating' : 'Creating' }}" class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-zinc-800">
            {{ isset($note) ? 'Update note' : 'Create note' }}
        </button>
    </div>
</div>
