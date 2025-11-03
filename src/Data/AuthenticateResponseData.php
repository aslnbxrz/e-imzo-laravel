<?php

declare(strict_types=1);

namespace Aslnbxrz\EImzo\Data;

final class AuthenticateResponseData extends ResponseData
{
    public function __construct(
        public readonly int     $status,
        public readonly ?array  $subjectCertificateInfo = null,
        public readonly ?string $message = null
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            status: (int)($data['status'] ?? 0),
            subjectCertificateInfo: $data['subjectCertificateInfo'] ?? null,
            message: $data['message'] ?? null
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 1;
    }
}
