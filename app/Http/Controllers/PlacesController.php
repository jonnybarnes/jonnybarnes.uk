<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Place;
use Illuminate\View\View;

/**
 * @psalm-suppress UnusedClass
 */
class PlacesController extends Controller
{
    /**
     * Show all the places.
     */
    public function index(): View
    {
        $places = Place::all();

        return view('allplaces', ['places' => $places]);
    }

    /**
     * Show a specific place.
     */
    public function show(Place $place): View
    {
        return view('singleplace', ['place' => $place]);
    }
}
