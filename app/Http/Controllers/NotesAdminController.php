<?php

namespace App\Http\Controllers;

use App\Note;
use Validator;
use Illuminate\Http\Request;
use App\Services\NoteService;

class NotesAdminController extends Controller
{
    /**
     * Show the form to make a new note.
     *
     * @return \Illuminate\View\Factory view
     */
    public function newNotePage()
    {
        return view('admin.newnote');
    }

    /**
     * List the notes that can be edited.
     *
     * @return \Illuminate\View\Factory view
     */
    public function listNotesPage()
    {
        $notes = Note::select('id', 'note')->orderBy('id', 'desc')->get();
        foreach ($notes as $note) {
            $note->originalNote = $note->getOriginal('note');
        }

        return view('admin.listnotes', ['notes' => $notes]);
    }

    /**
     * Display the form to edit a specific note.
     *
     * @param  string The note id
     * @return \Illuminate\View\Factory view
     */
    public function editNotePage($noteId)
    {
        $note = Note::find($noteId);
        $note->originalNote = $note->getOriginal('note');

        return view('admin.editnote', ['id' => $noteId, 'note' => $note]);
    }

    /**
     * Process a request to make a new note.
     *
     * @param Illuminate\Http\Request $request
     * @todo  Sort this mess out
     */
    public function createNote(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            ['photo' => 'photosize'],
            ['photosize' => 'At least one uploaded file exceeds size limit of 5MB']
        );
        if ($validator->fails()) {
            return redirect('/admin/note/new')
                ->withErrors($validator)
                ->withInput();
        }

        $note = $this->noteService->createNote($request);

        return view('admin.newnotesuccess', [
            'id' => $note->id,
            'shorturl' => $note->shorturl,
        ]);
    }

    /**
     * Process a request to edit a note. Easy since this can only be done
     * from the admin CP.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function editNote($noteId, Request $request)
    {
        //update note data
        $note = Note::find($noteId);
        $note->note = $request->input('content');
        $note->in_reply_to = $request->input('in-reply-to');
        $note->save();

        if ($request->input('webmentions')) {
            $wmc = new WebMentionsController();
            $wmc->send($note);
        }

        return view('admin.editnotesuccess', ['id' => $noteId]);
    }
}
