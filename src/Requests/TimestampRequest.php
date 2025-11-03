<?php

declare(strict_types=1);

namespace Aslnbxrz\EImzo\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasStringBody;

final class TimestampRequest extends Request implements HasBody
{
    use HasStringBody;

    protected Method $method = Method::POST;

    public function __construct(protected readonly string $pkcs7) {}

    public function resolveEndpoint(): string
    {
        return '/frontend/timestamp/pkcs7';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept'       => 'application/json',
            'Content-Type' => 'text/plain',
        ];
    }

    protected function defaultBody(): string
    {
        return $this->pkcs7;
    }
}
