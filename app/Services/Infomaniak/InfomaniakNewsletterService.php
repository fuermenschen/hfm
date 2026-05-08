<?php

declare(strict_types=1);

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

        throw_if($token === '', InvalidArgumentException::class, 'Missing Infomaniak newsletter token configuration.');

        throw_unless(is_numeric((string) $domainId), InvalidArgumentException::class, 'Missing INFOMANIAK_NEWSLETTER_DOMAIN_ID configuration.');

        throw_unless(is_numeric((string) $groupId), InvalidArgumentException::class, 'Invalid INFOMANIAK_NEWSLETTER_GROUP_ID configuration.');

        $this->domainId = (int) $domainId;
        $this->groupId = (int) $groupId;

        $this->client = Http::baseUrl(rtrim($baseUrl, '/'))
            ->withToken($token)
            ->acceptJson()
            ->timeout((int) $this->config->get('services.infomaniak_newsletter.timeout', 10))
            ->connectTimeout((int) $this->config->get('services.infomaniak_newsletter.connect_timeout', 5))
            ->withUserAgent((string) $this->config->get('services.infomaniak_newsletter.user_agent', 'HFM Newsletter Client'));
    }

    public function registerSubscriber(string $firstName, string $email): bool
    {
        $input = [
            'first_name' => trim($firstName),
            'email' => strtolower(trim($email)),
        ];

        $validator = Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        throw_if($validator->fails(), ValidationException::class, $validator);

        $subscriberId = $this->findSubscriberIdByEmail($input['email']);

        if ($subscriberId !== null) {
            $this->addSubscriberToGroup($subscriberId);

            return true;
        }

        $this->createSubscriber($input['first_name'], $input['email']);

        return false;
    }

    public function unsubscribeSubscriber(string $email): void
    {
        $input = [
            'email' => strtolower(trim($email)),
        ];

        $validator = Validator::make($input, [
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        throw_if($validator->fails(), ValidationException::class, $validator);

        $subscriberId = $this->findSubscriberIdByEmail($input['email']);

        if ($subscriberId === null) {
            return;
        }

        $this->unsubscribeSubscriberById($subscriberId);
    }

    protected function findSubscriberIdByEmail(string $email): ?int
    {
        $response = $this->client
            ->post(sprintf('/1/newsletters/%d/subscribers/filter', $this->domainId), [
                'filter' => [
                    'search' => $email,
                ],
            ])
            ->throw();

        /** @var array<int, array{id:int|string,email?:string}> $subscribers */
        $subscribers = $response->json('data', []);

        foreach ($subscribers as $subscriber) {
            if (isset($subscriber['email']) && strtolower($subscriber['email']) === $email) {
                return (int) $subscriber['id'];
            }
        }

        return null;
    }

    protected function addSubscriberToGroup(int $subscriberId): void
    {
        $this->client
            ->post(sprintf('/1/newsletters/%d/groups/%d/subscribers/assign', $this->domainId, $this->groupId), [
                'subscriber_ids' => [$subscriberId],
            ])
            ->throw();
    }

    protected function unsubscribeSubscriberById(int $subscriberId): void
    {
        $this->client
            ->put(sprintf('/1/newsletters/%d/subscribers/unsubscribe', $this->domainId), [
                'select' => [
                    'all' => false,
                    'include' => [$subscriberId],
                ],
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
                ->post(sprintf('/1/newsletters/%d/subscribers', $this->domainId), $payload)
                ->throw();
        } catch (RequestException $requestException) {
            throw_if($requestException->response->status() !== 422, $requestException);

            $this->client
                ->post(sprintf('/1/newsletters/%d/subscribers', $this->domainId), [
                    'email' => $email,
                    'groups' => [$this->groupId],
                ])
                ->throw();
        }
    }
}
