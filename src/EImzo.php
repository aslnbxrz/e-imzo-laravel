<?php

declare(strict_types=1);

namespace Aslnbxrz\EImzo;

use Aslnbxrz\EImzo\Data\AuthenticateResponseData;
use Aslnbxrz\EImzo\Data\ChallengeResponseData;
use Aslnbxrz\EImzo\Data\ErrorResponseData;
use Aslnbxrz\EImzo\Data\TimestampResponseData;
use Aslnbxrz\EImzo\Data\VerifyResponseData;
use Aslnbxrz\EImzo\Requests\AuthenticateRequest;
use Aslnbxrz\EImzo\Requests\ChallengeRequest;
use Aslnbxrz\EImzo\Requests\TimestampRequest;
use Aslnbxrz\EImzo\Requests\VerifyRequest;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;
use JsonException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;

/**
 * E-IMZO integration service (API friendly, stateless).
 *
 * All methods return Laravel Data objects and NEVER swallow transport exceptions.
 * Transport-level failures (network, 5xx with throw()) are rethrown as Saloon exceptions.
 * Logical failures return ErrorResponseData with status 0.
 */
final readonly class EImzo
{
    /**
     * Get translation message for E-IMZO errors.
     */
    private function trans(string $key, ?string $default = null): string
    {
        return Lang::get("e-imzo::messages.{$key}", [], $default ?? $key);
    }

    public function __construct(
        private EImzoConnector $connector
    ) {}

    /**
     * Get challenge from E-IMZO server.
     *
     * @return ChallengeResponseData|ErrorResponseData
     *
     * @throws RequestException
     * @throws FatalRequestException
     */
    public function challenge(): ChallengeResponseData|ErrorResponseData
    {
        try {
            $response = $this->connector->send(new ChallengeRequest);

            if ($response->failed()) {
                $this->logHttpWarn('E-IMZO challenge failed', $response);

                return ErrorResponseData::fromArray([
                    'status'  => 0,
                    'message' => $this->trans('challenge_fail'),
                ]);
            }

            $data = $this->safeJson($response);
            $responseData = ChallengeResponseData::fromArray($data);

            if (!$responseData->isSuccess()) {
                return ErrorResponseData::fromArray([
                    'status'  => $responseData->status,
                    'message' => $responseData->message ?? $this->trans('challenge_fail'),
                ]);
            }

            return $responseData;
        } catch (RequestException|FatalRequestException $e) {
            $this->logTransportError('E-IMZO challenge request exception', $e);

            throw $e;
        }
    }

    /**
     * Authenticate with signed PKCS#7 over the issued challenge.
     *
     * @param  string  $pkcs7  Signed challenge in PKCS#7 format (base64 / PEM)
     * @param  string  $userIp  User IP address
     * @param  string  $host  Request host (for audit)
     * @return AuthenticateResponseData|ErrorResponseData
     *
     * @throws RequestException
     * @throws FatalRequestException
     */
    public function authenticate(string $pkcs7, string $userIp, string $host): AuthenticateResponseData|ErrorResponseData
    {
        try {
            $response = $this->connector->send(new AuthenticateRequest($pkcs7, $userIp, $host));

            if ($response->failed()) {
                $this->logHttpWarn('E-IMZO authentication failed', $response, ['ip' => $userIp]);

                return ErrorResponseData::fromArray([
                    'status'  => 0,
                    'message' => $this->trans('auth_fail'),
                ]);
            }

            $data = $this->safeJson($response);
            $responseData = AuthenticateResponseData::fromArray($data);

            if (!$responseData->isSuccess()) {
                return ErrorResponseData::fromArray([
                    'status'  => $responseData->status,
                    'message' => $responseData->message ?? $this->trans('auth_fail'),
                ]);
            }

            Log::info('E-IMZO authentication successful', [
                'ip'            => $userIp,
                'has_cert_info' => $responseData->subjectCertificateInfo !== null,
            ]);

            return $responseData;
        } catch (RequestException|FatalRequestException $e) {
            $this->logTransportError('E-IMZO authentication request exception', $e, [
                'ip' => $userIp,
            ]);

            throw $e;
        }
    }

    /**
     * Attach timestamp to PKCS#7 (raw text/plain to TSA).
     *
     * @param  string  $pkcs7  Raw PKCS#7 (base64 string), not JSON.
     * @return TimestampResponseData|ErrorResponseData
     *
     * @throws RequestException
     * @throws FatalRequestException
     */
    public function timestamp(string $pkcs7): TimestampResponseData|ErrorResponseData
    {
        try {
            $response = $this->connector->send(new TimestampRequest($pkcs7));

            if ($response->failed()) {
                $this->logHttpWarn('E-IMZO timestamp failed', $response);

                return ErrorResponseData::fromArray([
                    'status'  => 0,
                    'message' => $this->trans('timestamp_fail'),
                ]);
            }

            $data = $this->safeJson($response);
            $responseData = TimestampResponseData::fromArray($data);

            if (!$responseData->isSuccess()) {
                return ErrorResponseData::fromArray([
                    'status'  => $responseData->status,
                    'message' => $responseData->message ?? $this->trans('timestamp_reject'),
                ]);
            }

            return $responseData;
        } catch (RequestException|FatalRequestException $e) {
            $this->logTransportError('E-IMZO timestamp request exception', $e);

            throw $e;
        }
    }

    /**
     * Verify signature (attached/detached).
     * form-urlencoded
     *  - pkcs7wtst = string (required)
     *  - data64    = string (optional; if exists then detached)
     *
     * Success (expected): {status:1, pkcs7Info:{...}}
     *
     * @return VerifyResponseData|ErrorResponseData
     *
     * @throws RequestException
     * @throws FatalRequestException
     */
    public function verify(string $pkcs7wtst, ?string $data64 = null): VerifyResponseData|ErrorResponseData
    {
        try {
            $response = $this->connector->send(
                new VerifyRequest(pkcs7: $pkcs7wtst, data64: $data64)
            );

            if ($response->failed()) {
                $this->logHttpWarn('E-IMZO verify failed', $response, [
                    'mode' => $data64 ? 'detached' : 'attached',
                ]);

                return ErrorResponseData::fromArray([
                    'status'  => 0,
                    'message' => $this->trans('verify_fail'),
                ]);
            }

            $data = $this->safeJson($response);
            $responseData = VerifyResponseData::fromArray($data);

            if (!$responseData->isSuccess()) {
                return ErrorResponseData::fromArray([
                    'status'  => $responseData->status,
                    'message' => $responseData->message ?? $this->trans('verify_fail'),
                ]);
            }

            return $responseData;
        } catch (RequestException|FatalRequestException $e) {
            $this->logTransportError('E-IMZO verify request exception', $e, [
                'mode' => $data64 ? 'detached' : 'attached',
            ]);

            throw $e;
        }
    }

    /**
     * Soft health-check: returns TRUE only if challenge succeeds with status=1.
     */
    public function healthCheck(): bool
    {
        try {
            $result = $this->challenge();

            return $result instanceof ChallengeResponseData && $result->isSuccess();
        } catch (Exception $e) {
            Log::error('E-IMZO health check failed', ['message' => $e->getMessage()]);

            return false;
        }
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * Decode JSON safely, returning [] on JSON error (and logging once).
     *
     * @return array<string,mixed>
     */
    private function safeJson(Response $response): array
    {
        try {
            /** @var array<string,mixed> $json */
            $json = $response->json();

            return is_array($json) ? $json : [];
        } catch (JsonException $e) {
            Log::warning('E-IMZO invalid JSON response', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'error'  => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Unified warning log for non-2xx/failed HTTP responses.
     */
    private function logHttpWarn(string $msg, Response $response, array $extra = []): void
    {
        Log::warning($msg, array_merge($extra, [
            'status' => $response->status(),
            'body'   => $this->truncate($response->body()),
        ]));
    }

    /**
     * Unified error log for transport-level exceptions.
     *
     * @param  RequestException|FatalRequestException  $e
     */
    private function logTransportError(string $msg, \Throwable $e, array $extra = []): void
    {
        Log::error($msg, array_merge($extra, [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]));
    }

    /**
     * Prevents log bloat on huge bodies.
     */
    private function truncate(?string $value, int $limit = 2000): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strlen($value) > $limit ? (mb_substr($value, 0, $limit) . '... [truncated]') : $value;
    }
}