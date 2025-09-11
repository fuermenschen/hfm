<?php

namespace App\Services\Webling;

use App\Settings\WeblingApiSettings;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
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

    public function __construct(public WeblingApiSettings $settings, public ConfigRepository $config)
    {
        $baseUrl = (string) $this->settings->api_url;
        $apiKey = (string) $this->settings->api_key;
        $options = (array) $this->config->get('services.webling.options', []);

        if ($baseUrl === '' || $apiKey === '') {
            throw new InvalidArgumentException('Webling configuration is missing. Please set WEBLING_BASE_URL and WEBLING_API_KEY in settings.');
        }

        // Build a PendingRequest with base URL, headers and timeouts
        $this->client = Http::baseUrl(rtrim($baseUrl, '/').'/api/1/')
            ->withHeaders([
                // Webling uses an API key header; the official client sends it as "apikey"
                'apikey' => $apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout((int) ($options['timeout'] ?? 10))
            ->connectTimeout((int) ($options['connecttimeout'] ?? 5))
            ->withUserAgent((string) ($options['useragent'] ?? 'HFM Webling Client'));
    }

    /**
     * Get the underlying HTTP PendingRequest instance.
     */
    public function client(): PendingRequest
    {
        return $this->client;
    }

    /**
     * Perform a GET request against the Webling API.
     *
     * @param  string  $path  Path like "member/123" (no leading slash required)
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
     */
    public function put(string $path, array $data): Response
    {
        return $this->client->put(ltrim($path, '/'), $data);
    }

    /**
     * Perform a DELETE request against the Webling API.
     *
     * @param  string  $path  Path like "member/123"
     */
    public function delete(string $path): Response
    {
        return $this->client->delete(ltrim($path, '/'));
    }
}
