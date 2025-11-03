<?php

declare(strict_types=1);

namespace Aslnbxrz\EImzo\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasStringBody;

/**
 * Verify PKCS#7 with E-IMZO TSA:
 * - attached  -> POST /frontend/verify/attached   body = pkcs7
 * - detached  -> POST /frontend/verify/detached   body = data64|pkcs7
 * Content-Type: text/plain
 */
final class VerifyRequest extends Request
{
    use HasStringBody;

    protected Method $method = Method::POST;

    /**
     * @param string $pkcs7 Required.
     * @param string|null $data64 Optional.
     */
    public function __construct(
        protected readonly string  $pkcs7,
        protected readonly ?string $data64 = null
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return $this->data64 ? '/frontend/verify/detached' : '/frontend/verify/attached';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'text/plain',
        ];
    }

    public function defaultBody(): string
    {
        return $this->data64 ? ($this->data64 . '|' . $this->pkcs7) : $this->pkcs7;
    }
}
