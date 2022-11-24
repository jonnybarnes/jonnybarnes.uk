<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWebMentions;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotesController extends Controller
{
    /**
     * List the notes that can be edited.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $notes = Note::select('id', 'note')->orderBy('id', 'desc')->get();
        foreach ($notes as $note) {
            $note->originalNote = $note->getOriginal('note');
        }

        return view('admin.notes.index', compact('notes'));
    }

    /**
     * Show the form to make a new note.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('admin.notes.create');
    }

    /**
     * Process a request to make a new note.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        Note::create([
            'in_reply_to' => $request->input('in-reply-to'),
            'note' => $request->input('content'),
        ]);

        return redirect('/admin/notes');
    }

    /**
     * Display the form to edit a specific note.
     *
     * @param  int  $noteId
     * @return \Illuminate\View\View
     */
    public function edit(int $noteId): View
    {
        $note = Note::find($noteId);
        $note->originalNote = $note->getOriginal('note');

        return view('admin.notes.edit', compact('note'));
    }

    /**
     * Process a request to edit a note. Easy since this can only be done
     * from the admin CP.
     *
     * @param  int  $noteId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(int $noteId): RedirectResponse
    {
        //update note data
        $note = Note::findOrFail($noteId);
        $note->note = request()->input('content');
        $note->in_reply_to = request()->input('in-reply-to');
        $note->save();

        if (request()->input('webmentions')) {
            dispatch(new SendWebMentions($note));
        }

        return redirect('/admin/notes');
    }

    /**
     * Delete the note.
     *
     * @param  int  $noteId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $noteId): RedirectResponse
    {
        $note = Note::findOrFail($noteId);
        $note->delete();

        return redirect('/admin/notes');
    }
}
