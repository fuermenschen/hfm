<?php

namespace App\Http\Controllers;

use App\Services\Infomaniak\InfomaniakNewsletterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NewsletterSubscriptionController extends Controller
{
    public function show(Request $request, string $email): View
    {
        $status = (string) $request->session()->get('newsletter_unsubscribe_status', '');

        return view('pages.newsletter-unsubscribe', [
            'email' => $email,
            'isUnsubscribed' => $status === 'success',
            'hasError' => $status === 'error',
        ]);
    }

    public function update(Request $request, string $email, InfomaniakNewsletterService $newsletterService): RedirectResponse
    {
        try {
            $newsletterService->unsubscribeSubscriber($email);

            return redirect()
                ->to($request->fullUrl())
                ->with('newsletter_unsubscribe_status', 'success');
        } catch (\Throwable $exception) {
            Log::error('Newsletter unsubscribe API call failed.', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->to($request->fullUrl())
                ->with('newsletter_unsubscribe_status', 'error');
        }
    }
}
