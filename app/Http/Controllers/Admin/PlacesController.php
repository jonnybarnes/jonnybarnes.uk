<?php

namespace App\Http\Controllers\Admin;

use App\Place;
use Illuminate\Http\Request;
use App\Services\PlaceService;
use App\Http\Controllers\Controller;
use Phaza\LaravelPostgis\Geometries\Point;

class PlacesController extends Controller
{
    protected $placeService;

    public function __construct(PlaceService $placeService = null)
    {
        $this->placeService = $placeService ?? new PlaceService();
    }

    /**
     * List the places that can be edited.
     *
     * @return \Illuminate\View\Factory view
     */
    public function index()
    {
        $places = Place::all();

        return view('admin.places.index', compact('places'));
    }

    /**
     * Show the form to make a new place.
     *
     * @return \Illuminate\View\Factory view
     */
    public function create()
    {
        return view('admin.places.create');
    }

    /**
     * Process a request to make a new place.
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\View\Factory view
     */
    public function store(Request $request)
    {
        $data = [];
        $data['name'] = $request->name;
        $data['description'] = $request->description;
        $data['latitude'] = $request->latitude;
        $data['longitude'] = $request->longitude;
        $place = $this->placeService->createPlace($data);

        return redirect('/admin/places');
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

        return view('admin.places.edit', [
            'id' => $placeId,
            'name' => $place->name,
            'description' => $place->description,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
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

        return redirect('/admin/places');
    }
}
