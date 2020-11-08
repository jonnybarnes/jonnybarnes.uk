<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class SessionStoreController extends Controller
{
    /**
     * Save the selected colour scheme in the session.
     *
     * @return string[]
     */
    public function saveColour(): array
    {
        $css = request()->input('css');

        session(['css' => $css]);

        return ['status' => 'ok'];
    }
}
