<?php

namespace App\Http\Controllers\Admin;

use App\Contact;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Filesystem\Filesystem;

class ContactsController extends Controller
{
    /**
     * List the currect contacts that can be edited.
     *
     * @return \Illuminate\View\Factory view
     */
    public function index()
    {
        $contacts = Contact::all();

        return view('admin.listcontacts', ['contacts' => $contacts]);
    }

    /**
     * Display the form to add a new contact.
     *
     * @return \Illuminate\View\Factory view
     */
    public function create()
    {
        return view('admin.newcontact');
    }

    /**
     * Show the form to edit an existing contact.
     *
     * @param  string  The contact id
     * @return \Illuminate\View\Factory view
     */
    public function edit($contactId)
    {
        $contact = Contact::findOrFail($contactId);

        return view('admin.editcontact', ['contact' => $contact]);
    }

    /**
     * Show the form to confirm deleting a contact.
     *
     * @return \Illuminate\View\Factory view
     */
    public function delete($contactId)
    {
        return view('admin.deletecontact', ['id' => $contactId]);
    }

    /**
     * Process the request to add a new contact.
     *
     * @param  \Illuminate\Http|request $request
     * @return \Illuminate\View\Factory view
     */
    public function store(Request $request)
    {
        $contact = new Contact();
        $contact->name = $request->input('name');
        $contact->nick = $request->input('nick');
        $contact->homepage = $request->input('homepage');
        $contact->twitter = $request->input('twitter');
        $contact->facebook = $request->input('facebook');
        $contact->save();

        return view('admin.newcontactsuccess', ['id' => $contact->id]);
    }

    /**
     * Process the request to edit a contact.
     *
     * @todo   Allow saving profile pictures for people without homepages
     *
     * @param  string  The contact id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function update($contactId, Request $request)
    {
        $contact = Contact::findOrFail($contactId);
        $contact->name = $request->input('name');
        $contact->nick = $request->input('nick');
        $contact->homepage = $request->input('homepage');
        $contact->twitter = $request->input('twitter');
        $contact->facebook = $request->input('facebook');
        $contact->save();

        if ($request->hasFile('avatar')) {
            if ($request->input('homepage') != '') {
                $dir = parse_url($request->input('homepage'))['host'];
                $destination = public_path() . '/assets/profile-images/' . $dir;
                $filesystem = new Filesystem();
                if ($filesystem->isDirectory($destination) === false) {
                    $filesystem->makeDirectory($destination);
                }
                $request->file('avatar')->move($destination, 'image');
            }
        }

        return view('admin.editcontactsuccess');
    }

    /**
     * Process the request to delete a contact.
     *
     * @param  string  The contact id
     * @return \Illuminate\View\Factory view
     */
    public function destroy($contactId)
    {
        $contact = Contact::findOrFail($contactId);
        $contact->delete();

        return view('admin.deletecontactsuccess');
    }

    /**
     * Download the avatar for a contact.
     *
     * This method attempts to find the microformat marked-up profile image
     * from a given homepage and save it accordingly
     *
     * @param  string  The contact id
     * @return \Illuminate\View\Factory view
     */
    public function getAvatar($contactId)
    {
        $contact = Contact::findOrFail($contactId);
        $homepage = $contact->homepage;
        if (($homepage !== null) && ($homepage !== '')) {
            $client = new Client();
            try {
                $response = $client->get($homepage);
                $html = (string) $response->getBody();
                $mf2 = \Mf2\parse($html, $homepage);
            } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                return "Bad Response from $homepage";
            }
            $avatarURL = null; // Initialising
            foreach ($mf2['items'] as $microformat) {
                if ($microformat['type'][0] == 'h-card') {
                    $avatarURL = $microformat['properties']['photo'][0];
                    break;
                }
            }
            try {
                $avatar = $client->get($avatarURL);
            } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                return "Unable to get $avatarURL";
            }
            $directory = public_path() . '/assets/profile-images/' . parse_url($homepage)['host'];
            $filesystem = new Filesystem();
            if ($filesystem->isDirectory($directory) === false) {
                $filesystem->makeDirectory($directory);
            }
            $filesystem->put($directory . '/image', $avatar->getBody());

            return view('admin.getavatarsuccess', ['homepage' => parse_url($homepage)['host']]);
        }
    }
}
