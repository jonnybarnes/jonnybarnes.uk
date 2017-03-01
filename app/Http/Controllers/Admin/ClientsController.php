<?php

namespace App\Http\Controllers\Admin;

use App\MicropubClient;
use App\Http\Controllers\Controller;

class ClientsController extends Controller
{
    /**
     * Show a list of known clients.
     *
     * @return \Illuminate\View\Factory view
     */
    public function index()
    {
        $clients = MicropubClient::all();

        return view('admin.listclients', ['clients' => $clients]);
    }

    /**
     * Show form to add a client name.
     *
     * @return \Illuminate\View\Factory view
     */
    public function create()
    {
        return view('admin.newclient');
    }

    /**
     * Show a form to edit a client name.
     *
     * @param  string The client id
     * @return \Illuminate\View\Factory view
     */
    public function edit($clientId)
    {
        $client = MicropubClient::findOrFail($clientId);

        return view('admin.editclient', [
            'id' => $clientId,
            'client_url' => $client->client_url,
            'client_name' => $client->client_name,
        ]);
    }

    /**
     * Process the request to adda new client name.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function store(Request $request)
    {
        MicropubClient::create([
            'client_url' => $request->input('client_url'),
            'client_name' => $request->input('client_name'),
        ]);

        return view('admin.newclientsuccess');
    }

    /**
     * Process the request to edit a client name.
     *
     * @param  string  The client id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function update($clientId, Request $request)
    {
        $client = MicropubClient::findOrFail($clientId);
        if ($request->input('edit')) {
            $client->client_url = $request->input('client_url');
            $client->client_name = $request->input('client_name');
            $client->save();

            return view('admin.editclientsuccess');
        }
        if ($request->input('delete')) {
            $client->delete();

            return view('admin.deleteclientsuccess');
        }
    }
}
