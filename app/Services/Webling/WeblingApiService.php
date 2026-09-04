<?php

declare(strict_types=1);

namespace App\Services\Webling;

use App\Exceptions\Webling\WeblingApiException;
use App\Settings\WeblingApiSettings;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * Base service to interact with the Webling API using Laravel HTTP client.
 */
class WeblingApiService
{
    protected PendingRequest $client;

    // Webling pipeline currently not active in production flows.
    // TODO(dead-code): Remove temporary ignores when Webling integration is reintroduced.
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function __construct(public WeblingApiSettings $settings, public ConfigRepository $config)
    {
        $baseUrl = $this->settings->api_url;
        $apiKey = $this->settings->api_key;
        $options = (array) $this->config->get('services.webling.options', []);

        throw_if($baseUrl === '' || $apiKey === '', InvalidArgumentException::class, 'Webling configuration is missing. Please set WEBLING_BASE_URL and WEBLING_API_KEY in settings.');

        // Build a PendingRequest with base URL, headers and timeouts
        $this->client = Http::baseUrl(rtrim($baseUrl, '/').'/api/1/')
            ->withHeaders([
                // Webling uses an API key header; the official client sends it as "apikey"
                'apikey' => $apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout((int) ($options['timeout'] ?? 10))
            ->connectTimeout((int) ($options['connecttimeout'] ?? 5))
            ->withUserAgent((string) ($options['useragent'] ?? 'HFM Webling Client'))
            ->throw(static function (Response $response): never {
                throw WeblingApiException::fromResponse($response);
            });
    }

    /**
     * Get the underlying HTTP PendingRequest instance.
     */
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function client(): PendingRequest
    {
        return $this->client;
    }

    /**
     * Perform a GET request against the Webling API.
     *
     * @param  string  $path  Path like "member/123" (no leading slash required)
     *
     * @throws ConnectionException
     */
    public function get(string $path): Response
    {
        return $this->client->get(ltrim($path, '/'));
    }

    /**
     * Perform a POST request against the Webling API.
     *
     * @param  string  $path  Path like "member"
     * @param  array<string,mixed>  $data  JSON serializable payload
     *
     * @throws ConnectionException
     */
    public function post(string $path, array $data): Response
    {
        return $this->client->post(ltrim($path, '/'), $data);
    }

    /**
     * Perform a PUT request against the Webling API.
     *
     * @param  string  $path  Path like "member/123"
     * @param  array<string,mixed>  $data  JSON serializable payload
     *
     * @throws ConnectionException
     */
    public function put(string $path, array $data): Response
    {
        return $this->client->put(ltrim($path, '/'), $data);
    }

    /**
     * Perform a DELETE request against the Webling API.
     *
     * @param  string  $path  Path like "member/123"
     *
     * @throws ConnectionException
     */
    public function delete(string $path): Response
    {
        return $this->client->delete(ltrim($path, '/'));
    }
}
