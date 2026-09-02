<?php

declare(strict_types=1);

namespace App\Services\Webling\Letter;

use App\Services\Webling\Letter\Dto\LetterDraft;
use Illuminate\Http\Client\Response;

class LetterService
{
    // TODO(dead-code): Remove ignore when donor_event_invoices letter flow is reintroduced.
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function __construct(
        public LetterRenderer $renderer,
        public LetterSchemaValidator $validator,
        public LetterApiClient $client,
    ) {}

    /**
     * Orchestrates creation of a Webling letter PDF for the given debitor (invoice) id.
     *
     * Usage:
     * $service->createInvoiceLetter('Invoice Title', fn(LetterBuilder $b) => ... , $debitorId)
     */
    public function createInvoiceLetter(string $title, callable $configure, int $debitorId): Response
    {
        $builder = new LetterBuilder;
        $configure($builder);
        $draft = $builder->build();

        return $this->createFromDraft($draft, $title, $debitorId);
    }

    public function createFromDraft(LetterDraft $draft, string $title, int $debitorId): Response
    {
        $json = $this->renderer->render($draft);
        $this->validator->validate($json);

        return $this->client->createLetter($json, $debitorId, $title);
    }

    /**
     * Create a letter from persisted source snapshot data.
     *
     * @param  array<string,mixed>  $snapshot
     */
    // TODO(dead-code): Remove ignore when donor event invoice creation is reintroduced.
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function createFromSnapshot(array $snapshot, string $title, int $debitorId): Response
    {
        return $this->createFromDraft(LetterDraft::fromSnapshot($snapshot), $title, $debitorId);
    }
}
