<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(): View
    {
        $settings = Setting::first();

        return view('admin.settings.show', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = Setting::firstOrNew();
        $settings->winter_effect_enabled = $request->boolean('winter_effect_enabled');
        $settings->save();

        return redirect()->route('admin.settings.show');
    }
}
