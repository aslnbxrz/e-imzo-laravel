<?php

declare(strict_types=1);

namespace Aslnbxrz\EImzo;

use InvalidArgumentException;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

final class EImzoConnector extends Connector
{
    use AcceptsJson;

    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?? config('e-imzo.base_url') ?? config('services.e-imzo.base_url');

        if (empty($this->baseUrl) || !str_starts_with($this->baseUrl, 'http')) {
            throw new InvalidArgumentException(
                __('e-imzo::messages.invalid_base_url') ?: 'E-IMZO base URL is missing or invalid. Must start with http/https'
            );
        }
    }

    public function resolveBaseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    protected function defaultHeaders(): array
    {
        $headers = ['Accept' => 'application/json'];

        $apiKey = config('e-imzo.api_key');
        if (!empty($apiKey)) {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }

        return $headers;
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => 15,
            'connect_timeout' => 10,
        ];
    }
}
