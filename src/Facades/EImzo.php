<?php

declare(strict_types=1);

namespace Aslnbxrz\EImzo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aslnbxrz\EImzo\Data\ChallengeResponseData|\Aslnbxrz\EImzo\Data\ErrorResponseData challenge()
 * @method static \Aslnbxrz\EImzo\Data\AuthenticateResponseData|\Aslnbxrz\EImzo\Data\ErrorResponseData authenticate(string $pkcs7, string $userIp, string $host)
 * @method static \Aslnbxrz\EImzo\Data\TimestampResponseData|\Aslnbxrz\EImzo\Data\ErrorResponseData timestamp(string $pkcs7)
 * @method static \Aslnbxrz\EImzo\Data\VerifyResponseData|\Aslnbxrz\EImzo\Data\ErrorResponseData verify(string $pkcs7wtst, ?string $data64 = null)
 * @method static bool healthCheck()
 *
 * @see \Aslnbxrz\EImzo\EImzo
 */
final class EImzo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Aslnbxrz\EImzo\EImzo::class;
    }
}
