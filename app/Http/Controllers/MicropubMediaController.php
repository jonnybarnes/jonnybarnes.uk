<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\InvalidTokenException;
use App\Http\Responses\MicropubResponses;
use App\Jobs\ProcessMedia;
use App\Models\Media;
use App\Services\TokenService;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use Ramsey\Uuid\Uuid;

class MicropubMediaController extends Controller
{
    protected TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function getHandler(): JsonResponse
    {
        try {
            $tokenData = $this->tokenService->validateToken(request()->input('access_token'));
        } catch (InvalidTokenException $e) {
            $micropubResponses = new MicropubResponses();

            return $micropubResponses->invalidTokenResponse();
        }

        if ($tokenData->hasClaim('scope') === false) {
            $micropubResponses = new MicropubResponses();

            return $micropubResponses->tokenHasNoScopeResponse();
        }

        if (Str::contains($tokenData->getClaim('scope'), 'create') === false) {
            $micropubResponses = new MicropubResponses();

            return $micropubResponses->insufficientScopeResponse();
        }

        if (request()->input('q') === 'last') {
            try {
                $media = Media::latest()->whereDate('created_at', '>=', Carbon::now()->subMinutes(30))->firstOrFail();
            } catch (ModelNotFoundException $exception) {
                return response()->json(['url' => null]);
            }

            return response()->json(['url' => $media->url]);
        }

        if (request()->input('q') === 'source') {
            $limit = request()->input('limit', 10);
            $offset = request()->input('offset', 0);

            $media = Media::latest()->offset($offset)->limit($limit)->get();

            $media->transform(function ($mediaItem) {
                return [
                    'url' => $mediaItem->url,
                ];
            });

            return response()->json(['items' => $media]);
        }

        if (request()->has('q')) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'This server does not know how to handle this q parameter (' . request()->input('q') . ')',
            ], 400);
        }

        return response()->json(['status' => 'OK']);
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
            $micropubResponses = new MicropubResponses();

            return $micropubResponses->invalidTokenResponse();
        }

        if ($tokenData->hasClaim('scope') === false) {
            $micropubResponses = new MicropubResponses();

            return $micropubResponses->tokenHasNoScopeResponse();
        }

        if (Str::contains($tokenData->getClaim('scope'), 'create') === false) {
            $micropubResponses = new MicropubResponses();

            return $micropubResponses->insufficientScopeResponse();
        }

        if (request()->hasFile('file') === false) {
            return response()->json([
                'response' => 'error',
                'error' => 'invalid_request',
                'error_description' => 'No file was sent with the request',
            ], 400);
        }

        if (request()->file('file')->isValid() === false) {
            return response()->json([
                'response' => 'error',
                'error' => 'invalid_request',
                'error_description' => 'The uploaded file failed validation',
            ], 400);
        }

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
}
