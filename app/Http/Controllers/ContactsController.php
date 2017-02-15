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
            $contact->homepageHost = parse_url($contact->homepage, PHP_URL_HOST);
            $file = public_path() . '/assets/profile-images/' . $contact->homepageHost . '/image';
            $contact->image = ($filesystem->exists($file)) ?
                '/assets/profile-images/' . $contact->homepageHost . '/image'
            :
                '/assets/profile-images/default-image';
        }

        return view('contacts', compact('contacts'));
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
        $contact->homepageHost = parse_url($contact->homepage, PHP_URL_HOST);
        $file = public_path() . '/assets/profile-images/' . $contact->homepageHost . '/image';
        $contact->image = ($filesystem->exists($file)) ?
            '/assets/profile-images/' . $contact->homepageHost . '/image'
        :
            '/assets/profile-images/default-image';

        return view('contact', ['contact' => $contact]);
    }
}
