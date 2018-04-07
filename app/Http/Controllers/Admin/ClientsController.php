<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\MicropubClient;
use App\Http\Controllers\Controller;

class ClientsController extends Controller
{
    /**
     * Show a list of known clients.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        $clients = MicropubClient::all();

        return view('admin.clients.index', compact('clients'));
    }

    /**
     * Show form to add a client name.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function create()
    {
        return view('admin.clients.create');
    }

    /**
     * Process the request to adda new client name.
     *
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        MicropubClient::create([
            'client_url' => request()->input('client_url'),
            'client_name' => request()->input('client_name'),
        ]);

        return redirect('/admin/clients');
    }

    /**
     * Show a form to edit a client name.
     *
     * @param  int  $clientId
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function edit(int $clientId)
    {
        $client = MicropubClient::findOrFail($clientId);

        return view('admin.clients.edit', [
            'id' => $clientId,
            'client_url' => $client->client_url,
            'client_name' => $client->client_name,
        ]);
    }

    /**
     * Process the request to edit a client name.
     *
     * @param  int  $clientId
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function update(int $clientId)
    {
        $client = MicropubClient::findOrFail($clientId);
        $client->client_url = request()->input('client_url');
        $client->client_name = request()->input('client_name');
        $client->save();

        return redirect('/admin/clients');
    }

    /**
     * Process a request to delete a client.
     *
     * @param  int  $clientId
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function destroy(int $clientId)
    {
        MicropubClient::where('id', $clientId)->delete();

        return redirect('/admin/clients');
    }
}
