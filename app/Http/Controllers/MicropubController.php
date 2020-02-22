<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\InvalidTokenException;
use App\Jobs\ProcessMedia;
use App\Models\{Media, Place};
use App\Services\Micropub\{HCardService, HEntryService, UpdateService};
use App\Services\TokenService;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\{File, JsonResponse, Response, UploadedFile};
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Phaza\LaravelPostgis\Geometries\Point;
use Ramsey\Uuid\Uuid;

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
     * then passes over the info to the relevant Service class.
     *
     * @return JsonResponse
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

        if (request()->input('h') == 'card' || request()->input('type.0') == 'h-card') {
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
     * token, here we check whether the token is valid and respond
     * appropriately. Further if the request has the query parameter
     * syndicate-to we respond with the known syndication endpoints.
     *
     * @return JsonResponse
     */
    public function get(): JsonResponse
    {
        try {
            $tokenData = $this->tokenService->validateToken(request()->input('access_token'));
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
                '/([0-9.\-]+)/',
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
     * @return JsonResponse
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function media(): JsonResponse
    {
        try {
            $tokenData = $this->tokenService->validateToken(request()->input('access_token'));
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

        // put the file on S3 initially, the ProcessMedia job may edit this
        Storage::disk('s3')->putFileAs(
            'media',
            new File(storage_path('app') . '/' . $filename),
            $filename
        );

        ProcessMedia::dispatch($filename);

        return response()->json([
            'response' => 'created',
            'location' => $media->url,
        ], 201)->header('Location', $media->url);
    }

    /**
     * Return the relevant CORS headers to a pre-flight OPTIONS request.
     *
     * @return Response
     */
    public function mediaOptionsResponse(): Response
    {
        return response('OK', 200);
    }

    /**
     * Get the file type from the mime-type of the uploaded file.
     *
     * @param string $mimeType
     * @return string
     */
    private function getFileTypeFromMimeType(string $mimeType): string
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
        if (in_array($mimeType, $imageMimeTypes)) {
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
        if (in_array($mimeType, $videoMimeTypes)) {
            return 'video';
        }
        //try known audio types
        $audioMimeTypes = [
            'audio/midi',
            'audio/mpeg',
            'audio/ogg',
            'audio/x-m4a',
        ];
        if (in_array($mimeType, $audioMimeTypes)) {
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
     * @param array $request This is the info from request()->all()
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
     * @param UploadedFile $file
     * @return string
     * @throws Exception
     */
    private function saveFile(UploadedFile $file): string
    {
        $filename = Uuid::uuid4()->toString() . '.' . $file->extension();
        Storage::disk('local')->putFileAs('', $file, $filename);

        return $filename;
    }

    /**
     * Generate a response to be returned when the token has insufficient scope.
     *
     * @return JsonResponse
     */
    private function insufficientScopeResponse(): JsonResponse
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
     * @return JsonResponse
     */
    private function invalidTokenResponse(): JsonResponse
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
     * @return JsonResponse
     */
    private function tokenHasNoScopeResponse(): JsonResponse
    {
        return response()->json([
            'response' => 'error',
            'error' => 'invalid_request',
            'error_description' => 'The provided token has no scopes',
        ], 400);
    }
}
