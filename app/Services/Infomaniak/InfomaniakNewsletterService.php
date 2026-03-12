<?php

namespace App\Services\Infomaniak;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class InfomaniakNewsletterService
{
    protected PendingRequest $client;

    protected int $domainId;

    protected int $groupId;

    public function __construct(protected ConfigRepository $config)
    {
        $token = (string) $this->config->get('services.infomaniak_newsletter.token', '');
        $domainId = $this->config->get('services.infomaniak_newsletter.domain_id');
        $groupId = $this->config->get('services.infomaniak_newsletter.group_id', 275443);
        $baseUrl = (string) $this->config->get('services.infomaniak_newsletter.base_url', 'https://api.infomaniak.com');

        if (! str_starts_with($baseUrl, 'http://') && ! str_starts_with($baseUrl, 'https://')) {
            $baseUrl = 'https://'.$baseUrl;
        }

        if ($token === '') {
            throw new InvalidArgumentException('Missing Infomaniak newsletter token configuration.');
        }

        if (! is_numeric((string) $domainId)) {
            throw new InvalidArgumentException('Missing INFOMANIAK_NEWSLETTER_DOMAIN_ID configuration.');
        }

        if (! is_numeric((string) $groupId)) {
            throw new InvalidArgumentException('Invalid INFOMANIAK_NEWSLETTER_GROUP_ID configuration.');
        }

        $this->domainId = (int) $domainId;
        $this->groupId = (int) $groupId;

        $this->client = Http::baseUrl(rtrim($baseUrl, '/'))
            ->withToken($token)
            ->acceptJson()
            ->timeout((int) $this->config->get('services.infomaniak_newsletter.timeout', 10))
            ->connectTimeout((int) $this->config->get('services.infomaniak_newsletter.connect_timeout', 5))
            ->withUserAgent((string) $this->config->get('services.infomaniak_newsletter.user_agent', 'HFM Newsletter Client'));
    }

    public function registerSubscriber(string $firstName, string $email): void
    {
        $input = [
            'first_name' => trim($firstName),
            'email' => strtolower(trim($email)),
        ];

        $validator = Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $subscriberId = $this->findSubscriberIdByEmail($input['email']);

        if ($subscriberId !== null) {
            $this->addSubscriberToGroup($subscriberId);

            return;
        }

        $this->createSubscriber($input['first_name'], $input['email']);
    }

    protected function findSubscriberIdByEmail(string $email): ?int
    {
        $response = $this->client
            ->post("/1/newsletters/{$this->domainId}/subscribers/filter", [
                'filter' => [
                    'search' => $email,
                ],
            ])
            ->throw();

        /** @var array<int, array{id:int|string,email?:string}> $subscribers */
        $subscribers = $response->json('data', []);

        foreach ($subscribers as $subscriber) {
            if (isset($subscriber['email']) && strtolower((string) $subscriber['email']) === $email) {
                return (int) $subscriber['id'];
            }
        }

        return null;
    }

    protected function addSubscriberToGroup(int $subscriberId): void
    {
        $this->client
            ->post("/1/newsletters/{$this->domainId}/groups/{$this->groupId}/subscribers/assign", [
                'subscriber_ids' => [$subscriberId],
            ])
            ->throw();
    }

    protected function createSubscriber(string $firstName, string $email): void
    {
        $payload = [
            'email' => $email,
            'fields' => [
                'firstname' => $firstName,
            ],
            'groups' => [$this->groupId],
        ];

        try {
            $this->client
                ->post("/1/newsletters/{$this->domainId}/subscribers", $payload)
                ->throw();
        } catch (RequestException $exception) {
            if ($exception->response->status() !== 422) {
                throw $exception;
            }

            $this->client
                ->post("/1/newsletters/{$this->domainId}/subscribers", [
                    'email' => $email,
                    'groups' => [$this->groupId],
                ])
                ->throw();
        }
    }
}
