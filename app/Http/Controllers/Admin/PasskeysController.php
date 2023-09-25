<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * @psalm-suppress UnusedClass
 */
class PasskeysController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $passkeys = $user->passkey;

        return view('admin.passkeys.index', compact('passkeys'));
    }

    public function save(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|unique:App\Models\Passkey,passkey_id',
            'public_key' => 'required|file',
            'transports' => 'required|json',
            'challenge' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Passkey could not be saved (validation failed)',
            ]);
        }

        $validated = $validator->validated();

        if (
            !session()->has('challenge') ||
            $validated['challenge'] !== session('challenge')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Passkey could not be saved (challenge failed)',
            ]);
        }

        $passkey = new Passkey();
        $passkey->passkey_id = $validated['id'];
        $passkey->passkey = $validated['public_key']->get();
        $passkey->transports = json_decode($validated['transports'], true, 512, JSON_THROW_ON_ERROR);
        $passkey->user_id = auth()->user()->id;
        $passkey->save();

        return response()->json([
            'success' => true,
            'message' => 'Passkey saved successfully',
        ]);
    }

    public function init(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $passkeys = $user->passkey()->get();

        $existing = $passkeys->map(function (Passkey $passkey) {
            return [
                'id' => $passkey->passkey_id,
                'transports' => $passkey->transports,
                'type' => 'public-key',
            ];
        })->all();

        $challenge = Hash::make(random_bytes(32));
        session(['challenge' => $challenge]);

        return response()->json([
            'challenge' => $challenge,
            'userId' => $user->name,
            'existing' => $existing,
        ]);
    }
}
