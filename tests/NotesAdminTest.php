<?php

namespace App\Tests;

use BrowserKitTest;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NotesAdminTest extends BrowserKitTest
{
    use DatabaseTransactions;

    protected $appurl;
    protected $notesAdminController;

    public function setUp()
    {
        parent::setUp();
        $this->appurl = config('app.url');
        $this->notesAdminController = new \App\Http\Controllers\NotesAdminController();
    }

    public function testCreatedNoteDispatchesSendWebmentionsJob()
    {
        $this->expectsJobs(\App\Jobs\SendWebMentions::class);

        $this->withSession(['loggedin' => true])
             ->visit($this->appurl . '/admin/note/new')
             ->type('Mentioning', 'content')
             ->press('Submit');
    }
}
