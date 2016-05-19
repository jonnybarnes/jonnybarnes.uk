<?php

namespace App\Http\Controllers;

use App\Contact;
use Illuminate\Filesystem\Filesystem;

class ContactsController extends Controller
{
    /**
     * Show all the contacts.
     *
     * @return \Illuminate\View\Factory view
     */
    public function showAll()
    {
        $filesystem = new Filesystem();
        $contacts = Contact::all();
        foreach ($contacts as $contact) {
            $contact->homepagePretty = parse_url($contact->homepage)['host'];
            $file = public_path() . '/assets/profile-images/' . $contact->homepagePretty . '/image';
            $contact->image = ($filesystem->exists($file)) ?
                '/assets/profile-images/' . $contact->homepagePretty . '/image'
            :
                '/assets/profile-images/default-image';
        }

        return view('contacts', ['contacts' => $contacts]);
    }

    /**
     * Show a single contact.
     *
     * @return \Illuminate\View\Factory view
     */
    public function showSingle($nick)
    {
        $filesystem = new Filesystem();
        $contact = Contact::where('nick', '=', $nick)->firstOrFail();
        $contact->homepagePretty = parse_url($contact->homepage)['host'];
        $file = public_path() . '/assets/profile-images/' . $contact->homepagePretty . '/image';
        $contact->image = ($filesystem->exists($file)) ?
            '/assets/profile-images/' . $contact->homepagePretty . '/image'
        :
            '/assets/profile-images/default-image';

        return view('contact', ['contact' => $contact]);
    }
}
