<?php

namespace App\Http\Controllers\Admin;

use App\Note;
use Validator;
use Illuminate\Http\Request;
use App\Jobs\SendWebMentions;
use App\Services\NoteService;
use App\Http\Controllers\Controller;

class NotesAdminController extends Controller
{
    protected $noteService;

    public function __construct(NoteService $noteService = null)
    {
        $this->noteService = $noteService ?? new NoteService();
    }

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

        return view('admin.listnotes', ['notes' => $notes]);
    }

    /**
     * Show the form to make a new note.
     *
     * @return \Illuminate\View\Factory view
     */
    public function create()
    {
        return view('admin.newnote');
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

        return view('admin.editnote', ['id' => $noteId, 'note' => $note]);
    }

    /**
     * The delete note page.
     *
     * @param  int id
     * @return view
     */
    public function delete($noteId)
    {
        return view('admin.deletenote', ['id' => $id]);
    }

    /**
     * Process a request to make a new note.
     *
     * @param Illuminate\Http\Request $request
     * @todo  Sort this mess out
     */
    public function store(Request $request)
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

        return view('admin.editnotesuccess', ['id' => $noteId]);
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

        return view('admin.deletenotesuccess');
    }
}
