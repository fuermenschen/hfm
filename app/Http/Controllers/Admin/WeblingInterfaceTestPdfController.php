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

        abort_unless(
            is_string($path) && preg_match('/^webling\/test-[A-Za-z0-9-]+\.pdf$/', $path) === 1,
            403
        );

        $stream = Storage::disk('local')->readStream($path);
        abort_unless(is_resource($stream), 404);

        $filename = 'webling-schnittstellentest-'.now()->format('Ymd-His').'.pdf';

        return response()->streamDownload(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, $filename, ['Content-Type' => 'application/pdf']);
    }
}
