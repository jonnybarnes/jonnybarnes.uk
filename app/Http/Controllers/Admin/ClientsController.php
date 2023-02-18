<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MicropubClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientsController extends Controller
{
    /**
     * Show a list of known clients.
     */
    public function index(): View
    {
        $clients = MicropubClient::all();

        return view('admin.clients.index', compact('clients'));
    }

    /**
     * Show form to add a client name.
     */
    public function create(): View
    {
        return view('admin.clients.create');
    }

    /**
     * Process the request to adda new client name.
     */
    public function store(): RedirectResponse
    {
        MicropubClient::create([
            'client_url' => request()->input('client_url'),
            'client_name' => request()->input('client_name'),
        ]);

        return redirect('/admin/clients');
    }

    /**
     * Show a form to edit a client name.
     */
    public function edit(int $clientId): View
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
     */
    public function update(int $clientId): RedirectResponse
    {
        $client = MicropubClient::findOrFail($clientId);
        $client->client_url = request()->input('client_url');
        $client->client_name = request()->input('client_name');
        $client->save();

        return redirect('/admin/clients');
    }

    /**
     * Process a request to delete a client.
     */
    public function destroy(int $clientId): RedirectResponse
    {
        MicropubClient::where('id', $clientId)->delete();

        return redirect('/admin/clients');
    }
}
