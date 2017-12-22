<?php

namespace App\Http\Controllers\Admin;

use App\Models\Place;
use Illuminate\Http\Request;
use App\Services\PlaceService;
use App\Http\Controllers\Controller;
use Phaza\LaravelPostgis\Geometries\Point;

class PlacesController extends Controller
{
    protected $placeService;

    public function __construct(PlaceService $placeService)
    {
        $this->placeService = $placeService;
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
        $data = $request->only(['name', 'description', 'latitude', 'longitude']);
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

        return view('admin.places.edit', compact('place'));
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
        $place->icon = $request->icon;
        $place->save();

        return redirect('/admin/places');
    }

    /**
     * List the places we can merge with the current place.
     *
     * @param string Place id
     * @return Illuminate\View\Factory view
     */
    public function mergeIndex($placeId)
    {
        $first = Place::find($placeId);
        $results = Place::near(new Point($first->latitude, $first->longitude))->get();
        $places = [];
        foreach ($results as $place) {
            if ($place->slug !== $first->slug) {
                $places[] = $place;
            }
        }

        return view('admin.places.merge.index', compact('first', 'places'));
    }

    public function mergeEdit($place1_id, $place2_id)
    {
        $place1 = Place::find($place1_id);
        $place2 = Place::find($place2_id);

        return view('admin.places.merge.edit', compact('place1', 'place2'));
    }

    public function mergeStore(Request $request)
    {
        $place1 = Place::find($request->input('place1'));
        $place2 = Place::find($request->input('place2'));

        if ($request->input('delete') === '1') {
            foreach ($place1->notes as $note) {
                $note->place()->dissociate();
                $note->place()->associate($place2->id);
            }
            $place1->delete();
        }
        if ($request->input('delete') === '2') {
            foreach ($place2->notes as $note) {
                $note->place()->dissociate();
                $note->place()->associate($place1->id);
            }
            $place2->delete();
        }

        return redirect('/admin/places');
    }
}
