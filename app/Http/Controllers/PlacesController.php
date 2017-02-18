<?php

namespace App\Http\Controllers;

use App\Place;

class PlacesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $places = Place::all();

        return view('allplaces', ['places' => $places]);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $place = Place::where('slug', '=', $slug)->firstOrFail();

        return view('singleplace', ['place' => $place]);
    }
}
