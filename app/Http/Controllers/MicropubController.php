<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Monolog\Logger;
use Ramsey\Uuid\Uuid;
use App\Jobs\ProcessMedia;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Monolog\Handler\StreamHandler;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\{Request, Response};
use App\Exceptions\InvalidTokenException;
use App\Models\{Like, Media, Note, Place};
use Phaza\LaravelPostgis\Geometries\Point;
use Intervention\Image\Exception\NotReadableException;
use App\Services\Micropub\{HCardService, HEntryService, UpdateService};

class MicropubController extends Controller
{
    protected $tokenService;
    protected $hentryService;
    protected $hcardService;
    protected $updateService;

    public function __construct(
        TokenService $tokenService,
        HEntryService $hentryService,
        HCardService $hcardService,
        UpdateService $updateService
    ) {
        $this->tokenService = $tokenService;
        $this->hentryService = $hentryService;
        $this->hcardService = $hcardService;
        $this->updateService = $updateService;
    }

    /**
     * This function receives an API request, verifies the authenticity
     * then passes over the info to the relavent Service class.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function post(): JsonResponse
    {
        try {
            $tokenData = $this->tokenService->validateToken(request()->input('access_token'));
        } catch (InvalidTokenException $e) {
            return $this->invalidTokenResponse();
        }

        if ($tokenData->hasClaim('scope') === false) {
            return $this->tokenHasNoScopeResponse();
        }

        $this->logMicropubRequest(request()->all());

        if ((request()->input('h') == 'entry') || (request()->input('type.0') == 'h-entry')) {
            if (stristr($tokenData->getClaim('scope'), 'create') === false) {
                return $this->insufficientScopeResponse();
            }
            $location = $this->hentryService->process(request()->all(), $this->getCLientId());

            return response()->json([
                'response' => 'created',
                'location' => $location,
            ], 201)->header('Location', $location);
        }

        if (request()->input('h') == 'card' || request()->input('type')[0] == 'h-card') {
            if (stristr($tokenData->getClaim('scope'), 'create') === false) {
                return $this->insufficientScopeResponse();
            }
            $location = $this->hcardService->process(request()->all());

            return response()->json([
                'response' => 'created',
                'location' => $location,
            ], 201)->header('Location', $location);
        }

        if (request()->input('action') == 'update') {
            if (stristr($tokenData->getClaim('scope'), 'update') === false) {
                return $this->insufficientScopeResponse();
            }

            return $this->updateService->process(request()->all());
        }

        return response()->json([
            'response' => 'error',
            'error_description' => 'unsupported_request_type',
        ], 500);
    }

    /**
     * Respond to a GET request to the micropub endpoint.
     *
     * A GET request has been made to `api/post` with an accompanying
     * token, here we check wether the token is valid and respond
     * appropriately. Further if the request has the query parameter
     * synidicate-to we respond with the known syndication endpoints.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(): JsonResponse
    {
        try {
            $tokenData = $this->tokenService->validateToken(request()->bearerToken());
        } catch (InvalidTokenException $e) {
            return $this->invalidTokenResponse();
        }

        if (request()->input('q') === 'syndicate-to') {
            return response()->json([
                'syndicate-to' => config('syndication.targets'),
            ]);
        }

        if (request()->input('q') == 'config') {
            return response()->json([
                'syndicate-to' => config('syndication.targets'),
                'media-endpoint' => route('media-endpoint'),
            ]);
        }

        if (request()->has('q') && substr(request()->input('q'), 0, 4) === 'geo:') {
            preg_match_all(
                '/([0-9\.\-]+)/',
                request()->input('q'),
                $matches
            );
            $distance = (count($matches[0]) == 3) ? 100 * $matches[0][2] : 1000;
            $places = Place::near(new Point($matches[0][0], $matches[0][1]))->get();

            return response()->json([
                'response' => 'places',
                'places' => $places,
            ]);
        }

        // default response is just to return the token data
        return response()->json([
            'response' => 'token',
            'token' => [
                'me' => $tokenData->getClaim('me'),
                'scope' => $tokenData->getClaim('scope'),
                'client_id' => $tokenData->getClaim('client_id'),
            ],
        ]);
    }

    /**
     * Process a media item posted to the media endpoint.
     *
     * @return Illuminate\Http\JsonResponse
     */
    public function media(): JsonResponse
    {
        try {
            $tokenData = $this->tokenService->validateToken(request()->bearerToken());
        } catch (InvalidTokenException $e) {
            return $this->invalidTokenResponse();
        }

        if ($tokenData->hasClaim('scope') === false) {
            return $this->tokenHasNoScopeResponse();
        }

        if (stristr($tokenData->getClaim('scope'), 'create') === false) {
            return $this->insufficientScopeResponse();
        }

        if ((request()->hasFile('file') && request()->file('file')->isValid()) === false) {
            return response()->json([
                'response' => 'error',
                'error' => 'invalid_request',
                'error_description' => 'The uploaded file failed validation',
            ], 400);
        }

        $this->logMicropubRequest(request()->all());

        $filename = $this->saveFile(request()->file('file'));

        $manager = resolve(ImageManager::class);
        try {
            $image = $manager->make(request()->file('file'));
            $width = $image->width();
        } catch (NotReadableException $exception) {
            // not an image
            $width = null;
        }

        $media = Media::create([
            'token' => request()->bearerToken(),
            'path' => 'media/' . $filename,
            'type' => $this->getFileTypeFromMimeType(request()->file('file')->getMimeType()),
            'image_widths' => $width,
        ]);

        ProcessMedia::dispatch($filename);

        return response()->json([
            'response' => 'created',
            'location' => $media->url,
        ], 201)->header('Location', $media->url);
    }

    /**
     * Get the file type from the mimetype of the uploaded file.
     *
     * @param  string  $mimetype
     * @return string
     */
    private function getFileTypeFromMimeType(string $mimetype): string
    {
        //try known images
        $imageMimeTypes = [
            'image/gif',
            'image/jpeg',
            'image/png',
            'image/svg+xml',
            'image/tiff',
            'image/webp',
        ];
        if (in_array($mimetype, $imageMimeTypes)) {
            return 'image';
        }
        //try known video
        $videoMimeTypes = [
            'video/mp4',
            'video/mpeg',
            'video/ogg',
            'video/quicktime',
            'video/webm',
        ];
        if (in_array($mimetype, $videoMimeTypes)) {
            return 'video';
        }
        //try known audio types
        $audioMimeTypes = [
            'audio/midi',
            'audio/mpeg',
            'audio/ogg',
            'audio/x-m4a',
        ];
        if (in_array($mimetype, $audioMimeTypes)) {
            return 'audio';
        }

        return 'download';
    }

    /**
     * Determine the client id from the access token sent with the request.
     *
     * @return string
     */
    private function getClientId(): string
    {
        return resolve(TokenService::class)
            ->validateToken(request()->input('access_token'))
            ->getClaim('client_id');
    }

    /**
     * Save the details of the micropub request to a log file.
     *
     * @param  array  $request This is the info from request()->all()
     */
    private function logMicropubRequest(array $request)
    {
        $logger = new Logger('micropub');
        $logger->pushHandler(new StreamHandler(storage_path('logs/micropub.log')), Logger::DEBUG);
        $logger->debug('MicropubLog', $request);
    }

    /**
     * Save an uploaded file to the local disk.
     *
     * @param  \Illuminate\Http\UploadedFele  $file
     * @return string $filename
     */
    private function saveFile(UploadedFile $file): string
    {
        $filename = Uuid::uuid4() . '.' . $file->extension();
        Storage::disk('local')->put($filename, $file);

        return $filename;
    }

    /**
     * Generate a response to be returned when the token has insufficient scope.
     *
     * @return \Illuminate\Http\JsonRepsonse
     */
    private function insufficientScopeResponse()
    {
        return response()->json([
            'response' => 'error',
            'error' => 'insufficient_scope',
            'error_description' => 'The token’s scope does not have the necessary requirements.',
        ], 401);
    }

    /**
     * Generate a response to be returned when the token is invalid.
     *
     * @return \Illuminate\Http\JsonRepsonse
     */
    private function invalidTokenResponse()
    {
        return response()->json([
            'response' => 'error',
            'error' => 'invalid_token',
            'error_description' => 'The provided token did not pass validation',
        ], 400);
    }

    /**
     * Generate a response to be returned when the token has no scope.
     *
     * @return \Illuminate\Http\JsonRepsonse
     */
    private function tokenHasNoScopeResponse()
    {
        return response()->json([
            'response' => 'error',
            'error' => 'invalid_request',
            'error_description' => 'The provided token has no scopes',
        ], 400);
    }
}
