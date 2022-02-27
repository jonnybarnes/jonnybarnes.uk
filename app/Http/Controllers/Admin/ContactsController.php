<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use GuzzleHttp\Client;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class ContactsController extends Controller
{
    /**
     * List the currect contacts that can be edited.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $contacts = Contact::all();

        return view('admin.contacts.index', compact('contacts'));
    }

    /**
     * Display the form to add a new contact.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('admin.contacts.create');
    }

    /**
     * Process the request to add a new contact.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(): RedirectResponse
    {
        $contact = new Contact();
        $contact->name = request()->input('name');
        $contact->nick = request()->input('nick');
        $contact->homepage = request()->input('homepage');
        $contact->twitter = request()->input('twitter');
        $contact->facebook = request()->input('facebook');
        $contact->save();

        return redirect('/admin/contacts');
    }

    /**
     * Show the form to edit an existing contact.
     *
     * @param  int  $contactId
     * @return \Illuminate\View\View
     */
    public function edit(int $contactId): View
    {
        $contact = Contact::findOrFail($contactId);

        return view('admin.contacts.edit', compact('contact'));
    }

    /**
     * Process the request to edit a contact.
     *
     * @todo   Allow saving profile pictures for people without homepages
     *
     * @param  int  $contactId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(int $contactId): RedirectResponse
    {
        $contact = Contact::findOrFail($contactId);
        $contact->name = request()->input('name');
        $contact->nick = request()->input('nick');
        $contact->homepage = request()->input('homepage');
        $contact->twitter = request()->input('twitter');
        $contact->facebook = request()->input('facebook');
        $contact->save();

        if (request()->hasFile('avatar') && (request()->input('homepage') != '')) {
            $dir = parse_url(request()->input('homepage'), PHP_URL_HOST);
            $destination = public_path() . '/assets/profile-images/' . $dir;
            $filesystem = new Filesystem();
            if ($filesystem->isDirectory($destination) === false) {
                $filesystem->makeDirectory($destination);
            }
            request()->file('avatar')->move($destination, 'image');
        }

        return redirect('/admin/contacts');
    }

    /**
     * Process the request to delete a contact.
     *
     * @param  int  $contactId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $contactId): RedirectResponse
    {
        $contact = Contact::findOrFail($contactId);
        $contact->delete();

        return redirect('/admin/contacts');
    }

    /**
     * Download the avatar for a contact.
     *
     * This method attempts to find the microformat marked-up profile image
     * from a given homepage and save it accordingly
     *
     * @param  int  $contactId
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function getAvatar(int $contactId)
    {
        // Initialising
        $avatarURL = null;
        $avatar = null;
        $contact = Contact::findOrFail($contactId);
        if ($contact->homepage !== null && mb_strlen($contact->homepage) !== 0) {
            $client = resolve(Client::class);
            try {
                $response = $client->get($contact->homepage);
            } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                return redirect('/admin/contacts/' . $contactId . '/edit')
                    ->with('error', 'Bad resposne from contact’s homepage');
            }
            $mf2 = \Mf2\parse((string) $response->getBody(), $contact->homepage);
            foreach ($mf2['items'] as $microformat) {
                if (Arr::get($microformat, 'type.0') === 'h-card') {
                    $avatarURL = Arr::get($microformat, 'properties.photo.0.value');
                    break;
                }
            }
            if ($avatarURL !== null) {
                try {
                    $avatar = $client->get($avatarURL);
                } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                    return redirect('/admin/contacts/' . $contactId . '/edit')
                        ->with('error', 'Unable to download avatar');
                }
            }
            if ($avatar !== null) {
                $directory = public_path() . '/assets/profile-images/' . parse_url($contact->homepage, PHP_URL_HOST);
                $filesystem = new Filesystem();
                if ($filesystem->isDirectory($directory) === false) {
                    $filesystem->makeDirectory($directory);
                }
                $filesystem->put($directory . '/image', $avatar->getBody());

                return view('admin.contacts.getavatarsuccess', [
                    'homepage' => parse_url($contact->homepage, PHP_URL_HOST),
                ]);
            }
        }

        return redirect('/admin/contacts/' . $contactId . '/edit');
    }
}
