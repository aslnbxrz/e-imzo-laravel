<?php

declare(strict_types=1);

namespace Aslnbxrz\EImzo\Data;

final class ChallengeResponseData extends ResponseData
{
    public function __construct(
        public readonly int     $status,
        public readonly string  $challenge,
        public readonly ?int    $ttl = null,
        public readonly ?string $message = null
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            status: (int)($data['status'] ?? 0),
            challenge: (string)($data['challenge'] ?? ''),
            ttl: isset($data['ttl']) ? (int)$data['ttl'] : null,
            message: $data['message'] ?? null
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 1;
    }
}
