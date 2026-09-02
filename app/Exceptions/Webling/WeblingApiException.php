<?php

declare(strict_types=1);

namespace App\Exceptions\Webling;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

class WeblingApiException extends RequestException
{
    public const string NotFound = 'not_found';

    public const string Authentication = 'authentication';

    public const string RateLimited = 'rate_limited';

    public const string Transient = 'transient';

    public const string Unexpected = 'unexpected';

    public function __construct(Response $response, public readonly string $category)
    {
        parent::__construct($response);
    }

    public static function fromResponse(Response $response): self
    {
        $status = $response->status();

        $category = match (true) {
            $status === 404 => self::NotFound,
            in_array($status, [401, 403], true) => self::Authentication,
            $status === 429 => self::RateLimited,
            $status === 408 || $status === 425 || in_array($status, [500, 503], true) => self::Transient,
            default => self::Unexpected,
        };

        return new self($response, $category);
    }

    // TODO(dead-code): Remove ignore when donor invoice actions consume error categories.
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function errorCategory(): string
    {
        return $this->category;
    }
}
