<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Place;

class PlacesController extends Controller
{
    /**
     * Show all the places.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        $places = Place::all();

        return view('allplaces', ['places' => $places]);
    }

    /**
     * Show a specific place.
     *
     * @param  string  $slug
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function show(string $slug)
    {
        $place = Place::where('slug', '=', $slug)->firstOrFail();

        return view('singleplace', ['place' => $place]);
    }
}
