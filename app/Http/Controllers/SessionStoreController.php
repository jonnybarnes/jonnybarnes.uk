<?php

namespace App\Http\Controllers;

class SessionStoreController extends Controller
{
    public function saveColour()
    {
        $css = request()->input('css');

        session(['css' => $css]);

        return ['status' => 'ok'];
    }
}
