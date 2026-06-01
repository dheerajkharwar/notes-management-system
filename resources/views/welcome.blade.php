@include('notes.index', [
    'notes' => \App\Models\Note::query()->latest()->paginate(10),
    'query' => null,
    'isSearch' => false,
])
