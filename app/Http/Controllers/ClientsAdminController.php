<?php

namespace App\Http\Controllers;

use App\Client;

class ClientsAdminController extends Controller
{
    /**
     * Show a list of known clients.
     *
     * @return \Illuminate\View\Factory view
     */
    public function listClients()
    {
        $clients = Client::all();

        return view('admin.listclients', ['clients' => $clients]);
    }

    /**
     * Show form to add a client name.
     *
     * @return \Illuminate\View\Factory view
     */
    public function newClient()
    {
        return view('admin.newclient');
    }

    /**
     * Process the request to adda new client name.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function postNewClient(Request $request)
    {
        Client::create([
            'client_url' => $request->input('client_url'),
            'client_name' => $request->input('client_name'),
        ]);

        return view('admin.newclientsuccess');
    }

    /**
     * Show a form to edit a client name.
     *
     * @param  string The client id
     * @return \Illuminate\View\Factory view
     */
    public function editClient($clientId)
    {
        $client = Client::findOrFail($clientId);

        return view('admin.editclient', [
            'id' => $clientId,
            'client_url' => $client->client_url,
            'client_name' => $client->client_name,
        ]);
    }

    /**
     * Process the request to edit a client name.
     *
     * @param  string  The client id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function postEditClient($clientId, Request $request)
    {
        $client = Client::findOrFail($clientId);
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
