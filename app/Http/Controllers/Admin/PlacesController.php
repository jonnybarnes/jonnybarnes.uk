<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Services\PlaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * @psalm-suppress UnusedClass
 */
class PlacesController extends Controller
{
    protected PlaceService $placeService;

    public function __construct(PlaceService $placeService)
    {
        $this->placeService = $placeService;
    }

    /**
     * List the places that can be edited.
     */
    public function index(): View
    {
        $places = Place::all();

        return view('admin.places.index', compact('places'));
    }

    /**
     * Show the form to make a new place.
     */
    public function create(): View
    {
        return view('admin.places.create');
    }

    /**
     * Process a request to make a new place.
     */
    public function store(): RedirectResponse
    {
        $this->placeService->createPlace(
            request()->only([
                'name',
                'description',
                'latitude',
                'longitude',
            ])
        );

        return redirect('/admin/places');
    }

    /**
     * Display the form to edit a specific place.
     */
    public function edit(int $placeId): View
    {
        $place = Place::findOrFail($placeId);

        return view('admin.places.edit', compact('place'));
    }

    /**
     * Process a request to edit a place.
     */
    public function update(int $placeId): RedirectResponse
    {
        $place = Place::findOrFail($placeId);
        $place->name = request()->input('name');
        $place->description = request()->input('description');
        $place->latitude = request()->input('latitude');
        $place->longitude = request()->input('longitude');
        $place->icon = request()->input('icon');
        $place->save();

        return redirect('/admin/places');
    }

    /**
     * List the places we can merge with the current place.
     */
    public function mergeIndex(int $placeId): View
    {
        $first = Place::find($placeId);
        $results = Place::near((object) ['latitude' => $first->latitude, 'longitude' => $first->longitude])->get();
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
     */
    public function mergeEdit(int $placeId1, int $placeId2): View
    {
        $place1 = Place::find($placeId1);
        $place2 = Place::find($placeId2);

        return view('admin.places.merge.edit', compact('place1', 'place2'));
    }

    /**
     * Process the request to merge two places.
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
