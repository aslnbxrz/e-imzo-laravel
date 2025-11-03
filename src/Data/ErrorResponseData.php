<?php

declare(strict_types=1);

namespace Aslnbxrz\EImzo\Data;

final class ErrorResponseData extends ResponseData
{
    public function __construct(
        public readonly int    $status,
        public readonly string $message
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            status: (int)($data['status'] ?? 0),
            message: (string)($data['message'] ?? 'Unknown error')
        );
    }
}
