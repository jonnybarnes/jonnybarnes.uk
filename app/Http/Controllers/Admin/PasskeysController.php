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
use Random\RandomException;
use Throwable;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\Exception\WebauthnException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class PasskeysController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $passkeys = $user->passkey;

        return view('admin.passkeys.index', compact('passkeys'));
    }

    /**
     * @throws RandomException
     * @throws \JsonException
     */
    public function getCreateOptions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // RP Entity i.e. the application
        $rpEntity = PublicKeyCredentialRpEntity::create(
            name: config('app.name'),
            id: config('app.url'),
        );

        // User Entity
        $userEntity = PublicKeyCredentialUserEntity::create(
            name: $user->name,
            id: (string) $user->id,
            displayName: $user->name,
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
        );

        $publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::create(
            rp: $rpEntity,
            user: $userEntity,
            challenge: $challenge,
            pubKeyCredParams: $pubKeyCredParams,
            authenticatorSelection: $authenticatorSelectionCriteria,
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE
        );

        $attestationStatementSupportManager = new AttestationStatementSupportManager;
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport);
        $webauthnSerializerFactory = new WebauthnSerializerFactory(
            attestationStatementSupportManager: $attestationStatementSupportManager
        );
        $webauthnSerializer = $webauthnSerializerFactory->create();
        $publicKeyCredentialCreationOptions = $webauthnSerializer->serialize(
            data: $publicKeyCredentialCreationOptions,
            format: 'json'
        );

        $request->session()->put('create_options', $publicKeyCredentialCreationOptions);

        return JsonResponse::fromJsonString($publicKeyCredentialCreationOptions);
    }

    /**
     * @throws Throwable
     * @throws WebauthnException
     * @throws \JsonException
     */
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $publicKeyCredentialCreationOptionsData = session('create_options');
        // Unset session data to mitigate replay attacks
        $request->session()->forget('create_options');
        if (empty($publicKeyCredentialCreationOptionsData)) {
            throw new WebauthnException('No public key credential request options found');
        }

        $attestationStatementSupportManager = new AttestationStatementSupportManager;
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport);
        $webauthnSerializerFactory = new WebauthnSerializerFactory(
            attestationStatementSupportManager: $attestationStatementSupportManager
        );
        $webauthnSerializer = $webauthnSerializerFactory->create();

        $publicKeyCredential = $webauthnSerializer->deserialize(
            json_encode($request->all(), JSON_THROW_ON_ERROR),
            PublicKeyCredential::class,
            'json'
        );

        if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            throw new WebauthnException('Invalid response type');
        }

        $algorithmManager = new Manager;
        $algorithmManager->add(new Ed25519);
        $algorithmManager->add(new ES256);
        $algorithmManager->add(new RS256);

        $ceremonyStepManagerFactory = new CeremonyStepManagerFactory;
        $ceremonyStepManagerFactory->setAlgorithmManager($algorithmManager);
        $ceremonyStepManagerFactory->setAttestationStatementSupportManager(
            $attestationStatementSupportManager
        );
        $ceremonyStepManagerFactory->setExtensionOutputCheckerHandler(
            ExtensionOutputCheckerHandler::create()
        );
        $allowedOrigins = [];
        if (App::environment('local', 'development')) {
            $allowedOrigins = [config('app.url')];
        }
        $ceremonyStepManagerFactory->setAllowedOrigins($allowedOrigins);

        $authenticatorAttestationResponseValidator = AuthenticatorAttestationResponseValidator::create(
            ceremonyStepManager: $ceremonyStepManagerFactory->creationCeremony()
        );

        $publicKeyCredentialCreationOptions = $webauthnSerializer->deserialize(
            $publicKeyCredentialCreationOptionsData,
            PublicKeyCredentialCreationOptions::class,
            'json'
        );

        $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
            authenticatorAttestationResponse: $publicKeyCredential->response,
            publicKeyCredentialCreationOptions: $publicKeyCredentialCreationOptions,
            host: config('app.url')
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

    /**
     * @throws RandomException
     * @throws \JsonException
     */
    public function getRequestOptions(Request $request): JsonResponse
    {
        $publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(16),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED
        );

        $attestationStatementSupportManager = AttestationStatementSupportManager::create();
        $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());
        $factory = new WebauthnSerializerFactory(
            attestationStatementSupportManager: $attestationStatementSupportManager
        );
        $serializer = $factory->create();
        $publicKeyCredentialRequestOptions = $serializer->serialize(data: $publicKeyCredentialRequestOptions, format: 'json');

        $request->session()->put('request_options', $publicKeyCredentialRequestOptions);

        return JsonResponse::fromJsonString($publicKeyCredentialRequestOptions);
    }

    /**
     * @throws \JsonException
     */
    public function login(Request $request): JsonResponse
    {
        $requestOptions = session('request_options');
        $request->session()->forget('request_options');

        if (empty($requestOptions)) {
            return response()->json([
                'success' => false,
                'message' => 'No request options found',
            ], 400);
        }

        $attestationStatementSupportManager = new AttestationStatementSupportManager;
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport);

        $webauthnSerializerFactory = new WebauthnSerializerFactory(
            attestationStatementSupportManager: $attestationStatementSupportManager
        );
        $webauthnSerializer = $webauthnSerializerFactory->create();

        $publicKeyCredential = $webauthnSerializer->deserialize(
            json_encode($request->all(), JSON_THROW_ON_ERROR),
            PublicKeyCredential::class,
            'json'
        );

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

        $publicKeyCredentialSource = $webauthnSerializer->deserialize(
            $passkey->passkey,
            PublicKeyCredentialSource::class,
            'json'
        );

        $algorithmManager = new Manager;
        $algorithmManager->add(new Ed25519);
        $algorithmManager->add(new ES256);
        $algorithmManager->add(new RS256);

        $attestationStatementSupportManager = new AttestationStatementSupportManager;
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport);

        $ceremonyStepManagerFactory = new CeremonyStepManagerFactory;
        $ceremonyStepManagerFactory->setAlgorithmManager($algorithmManager);
        $ceremonyStepManagerFactory->setAttestationStatementSupportManager(
            $attestationStatementSupportManager
        );
        $ceremonyStepManagerFactory->setExtensionOutputCheckerHandler(
            ExtensionOutputCheckerHandler::create()
        );
        $allowedOrigins = [];
        if (App::environment('local', 'development')) {
            $allowedOrigins = [config('app.url')];
        }
        $ceremonyStepManagerFactory->setAllowedOrigins($allowedOrigins);

        $authenticatorAssertionResponseValidator = AuthenticatorAssertionResponseValidator::create(
            ceremonyStepManager: $ceremonyStepManagerFactory->requestCeremony()
        );

        $publicKeyCredentialRequestOptions = $webauthnSerializer->deserialize(
            $requestOptions,
            PublicKeyCredentialRequestOptions::class,
            'json'
        );

        try {
            $authenticatorAssertionResponseValidator->check(
                publicKeyCredentialSource: $publicKeyCredentialSource,
                authenticatorAssertionResponse: $publicKeyCredential->response,
                publicKeyCredentialRequestOptions: $publicKeyCredentialRequestOptions,
                host: config('app.url'),
                userHandle: null,
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
