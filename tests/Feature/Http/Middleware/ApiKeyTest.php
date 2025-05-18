<?php

use App\Http\Middleware\ApiKey;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('allows requests with a valid API key', function () {
    $middleware = new ApiKey;
    $request = Request::create('/', 'GET', [], [], [], ['HTTP_X-API-Key' => config('app.api_key')]);
    $next = fn ($req) => response('OK', 200);

    $response = $middleware->handle($request, $next);

    expect($response->getStatusCode())->toBe(Response::HTTP_OK);
});

it('rejects requests missing the API key', function () {
    $middleware = new ApiKey;
    $request = Request::create('/', 'GET');
    $next = fn ($req) => response('OK', 200);

    $response = $middleware->handle($request, $next);

    expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED);
    expect($response->getContent())->toContain('API key is missing');
});

it('rejects requests with an invalid API key', function () {
    $middleware = new ApiKey;
    $request = Request::create('/', 'GET', [], [], [], ['HTTP_X-API-Key' => 'invalid-key']);
    $next = fn ($req) => response('OK', 200);

    $response = $middleware->handle($request, $next);

    expect($response->getStatusCode())->toBe(Response::HTTP_FORBIDDEN);
    expect($response->getContent())->toContain('Invalid API key');
});
