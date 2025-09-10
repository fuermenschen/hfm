<?php

namespace App\Services\Webling;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use InvalidArgumentException;
use Webling\API\Client;

/**
 * Base service to interact with the Webling API client.
 */
class WeblingApiService
{
    protected Client $client;

    public function __construct(public ConfigRepository $config)
    {
        $baseUrl = (string) $this->config->get('services.webling.base_url');
        $apiKey = (string) $this->config->get('services.webling.api_key');
        $options = (array) $this->config->get('services.webling.options', []);

        if ($baseUrl === '' || $apiKey === '') {
            throw new InvalidArgumentException('Webling configuration is missing. Please set WEBLING_BASE_URL and WEBLING_API_KEY.');
        }

        $this->client = new Client($baseUrl, $apiKey, $options);
    }

    /**
     * Get the underlying Webling API client instance.
     */
    public function client(): Client
    {
        return $this->client;
    }

    /**
     * Perform a GET request against the Webling API.
     *
     * @param  string  $path  Path like "member/123" (no leading slash required)
     */
    public function get(string $path): \Webling\API\IResponse
    {
        return $this->client->get(ltrim($path, '/'));
    }

    /**
     * Perform a POST request against the Webling API.
     *
     * @param  string  $path  Path like "member"
     * @param  array<string,mixed>  $data  JSON serializable payload
     */
    public function post(string $path, array $data): \Webling\API\IResponse
    {
        return $this->client->post('/'.ltrim($path, '/'), $data);
    }

    /**
     * Perform a PUT request against the Webling API.
     *
     * @param  string  $path  Path like "member/123"
     * @param  array<string,mixed>  $data  JSON serializable payload
     */
    public function put(string $path, array $data): \Webling\API\IResponse
    {
        return $this->client->put('/'.ltrim($path, '/'), $data);
    }

    /**
     * Perform a DELETE request against the Webling API.
     *
     * @param  string  $path  Path like "member/123"
     */
    public function delete(string $path): \Webling\API\IResponse
    {
        return $this->client->delete('/'.ltrim($path, '/'));
    }
}
