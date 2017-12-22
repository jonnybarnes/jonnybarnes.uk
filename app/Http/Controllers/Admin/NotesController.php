<?php

namespace App\Http\Controllers\Admin;

use App\Models\Note;
use Illuminate\Http\Request;
use App\Jobs\SendWebMentions;
use App\Http\Controllers\Controller;

class NotesController extends Controller
{
    /**
     * List the notes that can be edited.
     *
     * @return \Illuminate\View\Factory view
     */
    public function index()
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
     * @return \Illuminate\View\Factory view
     */
    public function create()
    {
        return view('admin.notes.create');
    }

    /**
     * Process a request to make a new note.
     *
     * @param Illuminate\Http\Request $request
     * @todo  Sort this mess out
     */
    public function store(Request $request)
    {
        Note::create([
            'in-reply-to' => $request->input('in-reply-to'),
            'note' => $request->input('content'),
        ]);

        return redirect('/admin/notes');
    }

    /**
     * Display the form to edit a specific note.
     *
     * @param  string The note id
     * @return \Illuminate\View\Factory view
     */
    public function edit($noteId)
    {
        $note = Note::find($noteId);
        $note->originalNote = $note->getOriginal('note');

        return view('admin.notes.edit', compact('note'));
    }

    /**
     * Process a request to edit a note. Easy since this can only be done
     * from the admin CP.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function update($noteId, Request $request)
    {
        //update note data
        $note = Note::findOrFail($noteId);
        $note->note = $request->input('content');
        $note->in_reply_to = $request->input('in-reply-to');
        $note->save();

        if ($request->input('webmentions')) {
            dispatch(new SendWebMentions($note));
        }

        return redirect('/admin/notes');
    }

    /**
     * Delete the note.
     *
     * @param  int id
     * @return view
     */
    public function destroy($id)
    {
        $note = Note::findOrFail($id);
        $note->delete();

        return redirect('/admin/notes');
    }
}
