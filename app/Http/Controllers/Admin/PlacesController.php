<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Services\PlaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use MStaack\LaravelPostgis\Geometries\Point;

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
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $places = Place::all();

        return view('admin.places.index', compact('places'));
    }

    /**
     * Show the form to make a new place.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('admin.places.create');
    }

    /**
     * Process a request to make a new place.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(): RedirectResponse
    {
        $data = request()->only(['name', 'description', 'latitude', 'longitude']);
        $place = $this->placeService->createPlace($data);

        return redirect('/admin/places');
    }

    /**
     * Display the form to edit a specific place.
     *
     * @param  int  $placeId
     * @return \Illuminate\View\View
     */
    public function edit(int $placeId): View
    {
        $place = Place::findOrFail($placeId);

        return view('admin.places.edit', compact('place'));
    }

    /**
     * Process a request to edit a place.
     *
     * @param  int  $placeId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(int $placeId): RedirectResponse
    {
        $place = Place::findOrFail($placeId);
        $place->name = request()->input('name');
        $place->description = request()->input('description');
        $place->location = new Point(
            (float) request()->input('latitude'),
            (float) request()->input('longitude')
        );
        $place->icon = request()->input('icon');
        $place->save();

        return redirect('/admin/places');
    }

    /**
     * List the places we can merge with the current place.
     *
     * @param  int  $placeId
     * @return \Illuminate\View\View
     */
    public function mergeIndex(int $placeId): View
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

    /**
     * Show a form for merging two specific places.
     *
     * @param  int  $placeId1
     * @param  int  $placeId2
     * @return \Illuminate\View\View
     */
    public function mergeEdit(int $placeId1, int $placeId2): View
    {
        $place1 = Place::find($placeId1);
        $place2 = Place::find($placeId2);

        return view('admin.places.merge.edit', compact('place1', 'place2'));
    }

    /**
     * Process the request to merge two places.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function mergeStore(): RedirectResponse
    {
        $place1 = Place::find(request()->input('place1'));
        $place2 = Place::find(request()->input('place2'));

        if (request()->input('delete') === '1') {
            foreach ($place1->notes as $note) {
                $note->place()->dissociate();
                $note->place()->associate($place2->id);
            }
            $place1->delete();
        }
        if (request()->input('delete') === '2') {
            foreach ($place2->notes as $note) {
                $note->place()->dissociate();
                $note->place()->associate($place1->id);
            }
            $place2->delete();
        }

        return redirect('/admin/places');
    }
}
