<?php

namespace App\Http\Controllers;

use App\Note;
use Imagine\Image\Box;
use Imagine\Gd\Imagine;
use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;

class PhotosController extends Controller
{
    /**
     * Image box size limit for resizing photos.
     */
    public function __construct()
    {
        $this->imageResizeLimit = 800;
    }

    /**
     * Save an uploaded photo to the image folder.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  The associated note’s nb60 ID
     * @return bool
     */
    public function saveImage(Request $request, $nb60id)
    {
        if ($request->hasFile('photo') !== true) {
            return false;
        }
        $photoFilename = 'note-' . $nb60id;
        $path = public_path() . '/assets/img/notes/';
        $ext = $request->file('photo')->getClientOriginalExtension();
        $photoFilename .= '.' . $ext;
        $request->file('photo')->move($path, $photoFilename);

        return true;
    }

    /**
     * Prepare a photo for posting to twitter.
     *
     * @param  string  photo fileanme
     * @return string  small photo filename, or null
     */
    public function makeSmallPhotoForTwitter($photoFilename)
    {
        $imagine = new Imagine();
        $orig = $imagine->open(public_path() . '/assets/img/notes/' . $photoFilename);
        $size = [$orig->getSize()->getWidth(), $orig->getSize()->getHeight()];
        if ($size[0] > $this->imageResizeLimit || $size[1] > $this->imageResizeLimit) {
            $filenameParts = explode('.', $photoFilename);
            $preExt = count($filenameParts) - 2;
            $filenameParts[$preExt] .= '-small';
            $photoFilenameSmall = implode('.', $filenameParts);
            $aspectRatio = $size[0] / $size[1];
            $box = ($aspectRatio >= 1) ?
                [$this->imageResizeLimit, (int) round($this->imageResizeLimit / $aspectRatio)]
                :
                [(int) round($this->imageResizeLimit * $aspectRatio), $this->imageResizeLimit];
            $orig->resize(new Box($box[0], $box[1]))
                 ->save(public_path() . '/assets/img/notes/' . $photoFilenameSmall);

            return $photoFilenameSmall;
        }
    }

    /**
     * Get the image path for a note.
     *
     * @param  string $nb60id
     * @return string | null
     */
    public function getPhotoPath($nb60id)
    {
        $filesystem = new Filesystem();
        $photoDir = public_path() . '/assets/img/notes';
        $files = $filesystem->files($photoDir);
        foreach ($files as $file) {
            $parts = explode('.', $file);
            $name = $parts[0];
            $dirs = explode('/', $name);
            $actualname = last($dirs);
            if ($actualname == 'note-' . $nb60id) {
                $ext = $parts[1];
            }
        }
        if (isset($ext)) {
            return '/assets/img/notes/note-' . $nb60id . '.' . $ext;
        }
    }
}
