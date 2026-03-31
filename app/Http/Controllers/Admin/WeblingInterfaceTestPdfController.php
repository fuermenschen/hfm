<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeblingInterfaceTestPdfController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $encryptedPath = (string) $request->query('path', '');

        abort_if($encryptedPath === '', 404);

        try {
            $path = decrypt($encryptedPath);
        } catch (\Throwable $e) {
            abort(403);
        }

        abort_unless(is_string($path) && str_starts_with($path, 'webling/test-') && str_ends_with($path, '.pdf'), 403);
        abort_unless(Storage::disk('local')->exists($path), 404);

        $filename = 'webling-schnittstellentest-'.now()->format('Ymd-His').'.pdf';

        return response()->streamDownload(function () use ($path): void {
            $stream = Storage::disk('local')->readStream($path);

            if ($stream === false) {
                abort(404);
            }

            fpassthru($stream);
            fclose($stream);
        }, $filename, ['Content-Type' => 'application/pdf']);
    }
}
