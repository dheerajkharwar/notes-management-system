import './bootstrap';

document.querySelectorAll('form[data-loading]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const button = event.submitter;

        if (!button || button.disabled) {
            return;
        }

        const label = button.dataset.loadingText || 'Loading';
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.classList.add('cursor-wait', 'opacity-80');
        button.innerHTML = `
            <span class="inline-flex items-center gap-2">
                <span class="size-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                <span>${label}</span>
            </span>
        `;
    });
});

const app = document.querySelector('#notes-app');

if (app) {
    const state = {
        notes: [],
        page: 1,
        lastPage: 1,
    };

    const elements = {
        form: document.querySelector('#note-form'),
        formTitle: document.querySelector('#form-title'),
        noteId: document.querySelector('#note-id'),
        title: document.querySelector('#title'),
        content: document.querySelector('#content'),
        reset: document.querySelector('#reset-button'),
        list: document.querySelector('#notes-list'),
        status: document.querySelector('#status'),
        meta: document.querySelector('#result-meta'),
        searchForm: document.querySelector('#search-form'),
        search: document.querySelector('#search'),
        prev: document.querySelector('#prev-page'),
        next: document.querySelector('#next-page'),
    };

    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...options.headers,
            },
            ...options,
        });

        if (response.status === 204) {
            return null;
        }

        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || 'Request failed');
        }

        return payload;
    };

    const setStatus = (message, isError = false) => {
        elements.status.textContent = message;
        elements.status.classList.toggle('hidden', false);
        elements.status.classList.toggle('border-red-200', isError);
        elements.status.classList.toggle('text-red-700', isError);
    };

    const clearStatus = () => {
        elements.status.classList.add('hidden');
        elements.status.textContent = '';
    };

    const resetForm = () => {
        elements.noteId.value = '';
        elements.title.value = '';
        elements.content.value = '';
        elements.formTitle.textContent = 'Create note';
        clearStatus();
    };

    const actionButton = (label, onClick, tone = 'default') => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.className = tone === 'danger'
            ? 'rounded-md border border-red-200 px-3 py-1.5 text-sm font-medium text-red-700 transition hover:bg-red-50'
            : 'rounded-md border border-zinc-300 px-3 py-1.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100';
        button.addEventListener('click', onClick);

        return button;
    };

    const renderNotes = (items, scores = new Map()) => {
        elements.list.innerHTML = '';

        if (items.length === 0) {
            elements.list.innerHTML = '<div class="rounded-md border border-dashed border-zinc-300 p-6 text-sm text-zinc-500">No notes found.</div>';
            return;
        }

        items.forEach((note) => {
            const card = document.createElement('article');
            card.className = 'flex min-h-64 flex-col gap-4 rounded-md border border-zinc-200 p-4 transition hover:border-teal-300 hover:shadow-sm';
            card.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-950"></h3>
                        <p class="mt-1 text-xs font-medium uppercase text-zinc-500"></p>
                    </div>
                    <span class="hidden rounded-md bg-teal-50 px-2 py-1 text-xs font-semibold text-teal-800"></span>
                </div>
                <p class="line-clamp-5 whitespace-pre-line text-sm leading-6 text-zinc-700"></p>
                <div class="summary hidden rounded-md bg-zinc-50 p-3 text-sm leading-6 text-zinc-700"></div>
                <div class="mt-auto flex flex-wrap gap-2"></div>
            `;

            card.querySelector('h3').textContent = note.title;
            card.querySelector('p').textContent = new Date(note.updated_at).toLocaleString();
            card.querySelectorAll('p')[1].textContent = note.content;

            const score = scores.get(note.id);
            const badge = card.querySelector('span');
            if (score !== undefined) {
                badge.textContent = `${Math.round(score * 100)}% match`;
                badge.classList.remove('hidden');
            }

            const summary = card.querySelector('.summary');
            if (note.summary) {
                summary.textContent = note.summary;
                summary.classList.remove('hidden');
            }

            const actions = card.querySelector('div:last-child');
            actions.append(
                actionButton('Edit', () => editNote(note)),
                actionButton('Summary', () => summarizeNote(note.id)),
                actionButton('Delete', () => deleteNote(note.id), 'danger'),
            );

            elements.list.append(card);
        });
    };

    const loadNotes = async (page = 1) => {
        const payload = await request(`/api/notes?page=${page}&limit=10`);
        state.notes = payload.data;
        state.page = payload.meta.current_page;
        state.lastPage = payload.meta.last_page;
        elements.meta.textContent = `${payload.meta.total} notes, page ${state.page} of ${state.lastPage}`;
        renderNotes(state.notes);
    };

    const editNote = (note) => {
        elements.noteId.value = note.id;
        elements.title.value = note.title;
        elements.content.value = note.content;
        elements.formTitle.textContent = 'Edit note';
        elements.title.focus();
        clearStatus();
    };

    const saveNote = async (event) => {
        event.preventDefault();
        const id = elements.noteId.value;
        const method = id ? 'PUT' : 'POST';
        const url = id ? `/api/notes/${id}` : '/api/notes';

        try {
            await request(url, {
                method,
                body: JSON.stringify({
                    title: elements.title.value,
                    content: elements.content.value,
                }),
            });
            resetForm();
            await loadNotes(id ? state.page : 1);
            setStatus('Note saved.');
        } catch (error) {
            setStatus(error.message, true);
        }
    };

    const deleteNote = async (id) => {
        try {
            await request(`/api/notes/${id}`, { method: 'DELETE' });
            await loadNotes(state.page);
            setStatus('Note deleted.');
        } catch (error) {
            setStatus(error.message, true);
        }
    };

    const summarizeNote = async (id) => {
        try {
            await request(`/api/notes/${id}/summary`, { method: 'POST' });
            await loadNotes(state.page);
            setStatus('Summary generated.');
        } catch (error) {
            setStatus(error.message, true);
        }
    };

    const searchNotes = async (event) => {
        event.preventDefault();
        const query = elements.search.value.trim();

        if (!query) {
            await loadNotes(1);
            return;
        }

        try {
            const payload = await request(`/api/notes/search?q=${encodeURIComponent(query)}&limit=10`);
            const scores = new Map(payload.data.map((result) => [result.note.id, result.score]));
            elements.meta.textContent = `${payload.meta.count} semantic matches for "${payload.meta.query}"`;
            renderNotes(payload.data.map((result) => result.note), scores);
        } catch (error) {
            setStatus(error.message, true);
        }
    };

    elements.form.addEventListener('submit', saveNote);
    elements.reset.addEventListener('click', resetForm);
    elements.searchForm.addEventListener('submit', searchNotes);
    elements.prev.addEventListener('click', () => state.page > 1 && loadNotes(state.page - 1));
    elements.next.addEventListener('click', () => state.page < state.lastPage && loadNotes(state.page + 1));

    loadNotes().catch((error) => setStatus(error.message, true));
}
