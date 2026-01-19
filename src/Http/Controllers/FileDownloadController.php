<?php

namespace Tigusigalpa\FileOutput\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDownloadController
{
    public function download(Request $request): StreamedResponse
    {
        $disk = $request->get('disk');
        $path = base64_decode($request->get('path'));

        if (!$disk || !$path) {
            abort(404);
        }

        $storage = Storage::disk($disk);

        if (!$storage->exists($path)) {
            abort(404);
        }

        return $storage->download($path);
    }
}
