<?php

namespace App\Services\Webling;

use Webling\API\IResponse;

/**
 * Skeleton service for working with invoices via Webling API.
 *
 * This service will encapsulate invoice-related operations and use
 * the WeblingApiService for HTTP communication.
 */
class WeblingInvoiceService
{
    public function __construct(public WeblingApiService $api) {}

    /**
     * Create an invoice in Webling.
     *
     * @param  array<string,mixed>  $data  Invoice payload according to your Webling schema
     */
    public function createInvoice(array $data): IResponse
    {
        // TODO: adapt endpoint and payload to your Webling instance
        // Example placeholder endpoint – replace with the real one when known
        return $this->api->post('invoice', $data);
    }

    /**
     * Retrieve an invoice by ID.
     */
    public function getInvoice(int $id): IResponse
    {
        // TODO: adapt endpoint to your Webling instance
        return $this->api->get("invoice/{$id}");
    }

    /**
     * Update an invoice by ID.
     *
     * @param  array<string,mixed>  $data  Invoice payload updates
     */
    public function updateInvoice(int $id, array $data): IResponse
    {
        // TODO: adapt endpoint to your Webling instance
        return $this->api->put("invoice/{$id}", $data);
    }

    /**
     * Delete an invoice by ID.
     */
    public function deleteInvoice(int $id): IResponse
    {
        // TODO: adapt endpoint to your Webling instance
        return $this->api->delete("invoice/{$id}");
    }
}
