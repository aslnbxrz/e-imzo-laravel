<?php

declare(strict_types=1);

namespace Aslnbxrz\EImzo\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasStringBody;

final class AuthenticateRequest extends Request implements HasBody
{
    use HasStringBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected readonly string $pkcs7,
        protected readonly string $userIp,
        protected readonly string $host
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return '/backend/auth';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'text/plain',
            'X-Real-IP' => $this->userIp,
            'Host' => $this->host,
        ];
    }

    protected function defaultBody(): string
    {
        return $this->pkcs7;
    }
}
