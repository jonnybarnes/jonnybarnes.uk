<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\View;

/**
 * @psalm-suppress UnusedClass
 */
class ContactsController extends Controller
{
    /**
     * Show all the contacts.
     */
    public function index(): View
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

        return view('contacts.index', compact('contacts'));
    }

    /**
     * Show a single contact.
     */
    public function show(Contact $contact): View
    {
        $contact->homepageHost = parse_url($contact->homepage, PHP_URL_HOST);
        $file = public_path() . '/assets/profile-images/' . $contact->homepageHost . '/image';

        $filesystem = new Filesystem();
        $image = ($filesystem->exists($file)) ?
            '/assets/profile-images/' . $contact->homepageHost . '/image'
        :
            '/assets/profile-images/default-image';

        return view('contacts.show', compact('contact', 'image'));
    }
}
