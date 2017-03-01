<?php

namespace App\Http\Controllers\Admin;

use App\Place;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Phaza\LaravelPostgis\Geometries\Point;

class PlacesAdminController extends Controller
{
    /**
     * List the places that can be edited.
     *
     * @return \Illuminate\View\Factory view
     */
    public function index()
    {
        $places = Place::all();

        return view('admin.listplaces', ['places' => $places]);
    }

    /**
     * Show the form to make a new place.
     *
     * @return \Illuminate\View\Factory view
     */
    public function create()
    {
        return view('admin.newplace');
    }

    /**
     * Display the form to edit a specific place.
     *
     * @param  string The place id
     * @return \Illuminate\View\Factory view
     */
    public function edit($placeId)
    {
        $place = Place::findOrFail($placeId);

        $latitude = $place->getLatitude();
        $longitude = $place->getLongitude();

        return view('admin.editplace', [
            'id' => $placeId,
            'name' => $place->name,
            'description' => $place->description,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    /**
     * Process a request to make a new place.
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\View\Factory view
     */
    public function store(Request $request)
    {
        $this->placeService->createPlace($request);

        return view('admin.newplacesuccess');
    }

    /**
     * Process a request to edit a place.
     *
     * @param string The place id
     * @param Illuminate\Http\Request $request
     * @return Illuminate\View\Factory view
     */
    public function update($placeId, Request $request)
    {
        $place = Place::findOrFail($placeId);
        $place->name = $request->name;
        $place->description = $request->description;
        $place->location = new Point((float) $request->latitude, (float) $request->longitude);
        $place->save();

        return view('admin.editplacesuccess');
    }
}
