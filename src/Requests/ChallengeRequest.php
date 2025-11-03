<?php

declare(strict_types=1);

namespace Aslnbxrz\EImzo\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ChallengeRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/frontend/challenge';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}
