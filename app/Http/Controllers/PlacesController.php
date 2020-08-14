<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Place;
use Illuminate\View\View;

class PlacesController extends Controller
{
    /**
     * Show all the places.
     *
     * @return View
     */
    public function index(): View
    {
        $places = Place::all();

        return view('allplaces', ['places' => $places]);
    }

    /**
     * Show a specific place.
     *
     * @param Place $place
     * @return View
     */
    public function show(Place $place): View
    {
        return view('singleplace', ['place' => $place]);
    }
}
