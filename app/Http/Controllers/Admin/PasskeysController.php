<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Passkey;
use App\Models\User;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\EdDSA\Ed25519;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Throwable;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\Exception\WebauthnException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * @psalm-suppress UnusedClass
 */
class PasskeysController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $passkeys = $user->passkey;

        return view('admin.passkeys.index', compact('passkeys'));
    }

    public function getCreateOptions(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // RP Entity i.e. the application
        $rpEntity = PublicKeyCredentialRpEntity::create(
            config('app.name'),
            config('url.longurl'),
        );

        // User Entity
        $userEntity = PublicKeyCredentialUserEntity::create(
            $user->name,
            (string) $user->id,
            $user->name,
        );

        // Challenge
        $challenge = random_bytes(16);

        // List of supported public key parameters
        $pubKeyCredParams = collect([
            Algorithms::COSE_ALGORITHM_EDDSA,
            Algorithms::COSE_ALGORITHM_ES256,
            Algorithms::COSE_ALGORITHM_RS256,
        ])->map(
            fn ($algorithm) => PublicKeyCredentialParameters::create('public-key', $algorithm)
        )->toArray();

        $authenticatorSelectionCriteria = AuthenticatorSelectionCriteria::create(
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            requireResidentKey: true,
        );

        $options = PublicKeyCredentialCreationOptions::create(
            $rpEntity,
            $userEntity,
            $challenge,
            $pubKeyCredParams,
            authenticatorSelection: $authenticatorSelectionCriteria,
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE
        );

        $options = json_encode($options, JSON_THROW_ON_ERROR);

        session(['create_options' => $options]);

        return JsonResponse::fromJsonString($options);
    }

    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $publicKeyCredentialCreationOptionsData = session('create_options');
        if (empty($publicKeyCredentialCreationOptionsData)) {
            throw new WebAuthnException('No public key credential request options found');
        }
        $publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::createFromString($publicKeyCredentialCreationOptionsData);

        // Unset session data to mitigate replay attacks
        session()->forget('create_options');

        $attestationSupportManager = AttestationStatementSupportManager::create();
        $attestationSupportManager->add(NoneAttestationStatementSupport::create());
        $attestationObjectLoader = AttestationObjectLoader::create($attestationSupportManager);
        $publicKeyCredentialLoader = PublicKeyCredentialLoader::create($attestationObjectLoader);

        $publicKeyCredential = $publicKeyCredentialLoader->load(json_encode($request->all(), JSON_THROW_ON_ERROR));

        if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            throw new WebAuthnException('Invalid response type');
        }

        $attestationStatementSupportManager = AttestationStatementSupportManager::create();
        $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());

        $authenticatorAttestationResponseValidator = AuthenticatorAttestationResponseValidator::create(
            attestationStatementSupportManager: $attestationStatementSupportManager,
            publicKeyCredentialSourceRepository: null,
            tokenBindingHandler: null,
            extensionOutputCheckerHandler: ExtensionOutputCheckerHandler::create(),
        );

        $securedRelyingPartyId = [];
        if (App::environment('local', 'development')) {
            $securedRelyingPartyId = [config('url.longurl')];
        }

        $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
            authenticatorAttestationResponse: $publicKeyCredential->response,
            publicKeyCredentialCreationOptions: $publicKeyCredentialCreationOptions,
            request: config('url.longurl'),
            securedRelyingPartyId: $securedRelyingPartyId,
        );

        $user->passkey()->create([
            'passkey_id' => Base64UrlSafe::encodeUnpadded($publicKeyCredentialSource->publicKeyCredentialId),
            'passkey' => json_encode($publicKeyCredentialSource, JSON_THROW_ON_ERROR),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Passkey created successfully',
        ]);
    }

    public function getRequestOptions(): JsonResponse
    {
        $publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(16),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED
        );

        $publicKeyCredentialRequestOptions = json_encode($publicKeyCredentialRequestOptions, JSON_THROW_ON_ERROR);

        session(['request_options' => $publicKeyCredentialRequestOptions]);

        return JsonResponse::fromJsonString($publicKeyCredentialRequestOptions);
    }

    public function login(Request $request): JsonResponse
    {
        $requestOptions = session('request_options');
        session()->forget('request_options');

        if (empty($requestOptions)) {
            return response()->json([
                'success' => false,
                'message' => 'No request options found',
            ], 400);
        }

        $publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::createFromString($requestOptions);

        $attestationSupportManager = AttestationStatementSupportManager::create();
        $attestationSupportManager->add(NoneAttestationStatementSupport::create());
        $attestationObjectLoader = AttestationObjectLoader::create($attestationSupportManager);
        $publicKeyCredentialLoader = PublicKeyCredentialLoader::create($attestationObjectLoader);

        $publicKeyCredential = $publicKeyCredentialLoader->load(json_encode($request->all(), JSON_THROW_ON_ERROR));

        if (! $publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid response type',
            ], 400);
        }

        $passkey = Passkey::firstWhere('passkey_id', $publicKeyCredential->id);
        if (! $passkey) {
            return response()->json([
                'success' => false,
                'message' => 'Passkey not found',
            ], 404);
        }

        $credential = PublicKeyCredentialSource::createFromArray(json_decode($passkey->passkey, true, 512, JSON_THROW_ON_ERROR));

        $algorithmManager = Manager::create();
        $algorithmManager->add(new Ed25519());
        $algorithmManager->add(new ES256());
        $algorithmManager->add(new RS256());

        $authenticatorAssertionResponseValidator = new AuthenticatorAssertionResponseValidator(
            publicKeyCredentialSourceRepository: null,
            tokenBindingHandler: null,
            extensionOutputCheckerHandler: ExtensionOutputCheckerHandler::create(),
            algorithmManager: $algorithmManager,
        );

        $securedRelyingPartyId = [];
        if (App::environment('local', 'development')) {
            $securedRelyingPartyId = [config('url.longurl')];
        }

        try {
            $authenticatorAssertionResponseValidator->check(
                credentialId: $credential,
                authenticatorAssertionResponse: $publicKeyCredential->response,
                publicKeyCredentialRequestOptions: $publicKeyCredentialRequestOptions,
                request: config('url.longurl'),
                userHandle: null,
                securedRelyingPartyId: $securedRelyingPartyId,
            );
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Passkey could not be verified',
            ], 500);
        }

        $user = User::find($passkey->user_id);
        Auth::login($user);

        return response()->json([
            'success' => true,
            'message' => 'Passkey verified successfully',
        ]);
    }
}
