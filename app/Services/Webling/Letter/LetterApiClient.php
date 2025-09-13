<?php

namespace App\Services\Webling\Letter;

use App\Services\Webling\WeblingApiService;
use Illuminate\Http\Client\Response;

class LetterApiClient
{
    public function __construct(public WeblingApiService $api) {}

    /**
     * @param  array<string,mixed>  $dataJson  JSON-ready letter payload (will be json_encode'd)
     */
    public function createLetter(array $dataJson, int $debitorId, string $title = 'Rechnung HfM', string $state = 'sent', string $letterType = 'debitor'): Response
    {

        $payload = [
            'properties' => [
                'title' => $title,
                'state' => $state,
                'data' => json_encode($dataJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'lettertype' => $letterType,
            ],
            'links' => [
                'debitor' => [$debitorId],
            ],
        ];

        return $this->api->post('letter/new/send', $payload);
    }
}
