# E-IMZO Laravel Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aslnbxrz/e-imzo.svg?style=flat-square)](https://packagist.org/packages/aslnbxrz/e-imzo)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/aslnbxrz/e-imzo/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/aslnbxrz/e-imzo/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/aslnbxrz/e-imzo.svg?style=flat-square)](https://packagist.org/packages/aslnbxrz/e-imzo)

Professional Laravel package for E-IMZO (Electronic Signature) integration. This package provides a clean, type-safe interface for working with E-IMZO services including challenge generation, authentication, timestamping, and signature verification.

## Features

- ✅ **Type-Safe**: Uses Laravel Data objects for all responses
- ✅ **Clean API**: Simple and intuitive methods
- ✅ **Error Handling**: Comprehensive error handling with proper exception types
- ✅ **Health Checks**: Built-in health check functionality
- ✅ **Laravel Integration**: Full Laravel facade support
- ✅ **Strict Types**: PHP 8.4+ with strict types enabled

## Requirements

- PHP 8.4+
- Laravel 11.0+ or 12.0+
- E-IMZO service endpoint URL

## Installation

Install the package via Composer:

```bash
composer require aslnbxrz/e-imzo
```

### Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="e-imzo-config"
```

This will create `config/e-imzo.php`. You can set the configuration using environment variables:

```env
E_IMZO_BASE_URL=https://your-e-imzo-service.com
E_IMZO_API_KEY=your-api-key-here
```

Or configure it directly in `config/e-imzo.php`:

```php
return [
    'base_url' => env('E_IMZO_BASE_URL', 'https://your-e-imzo-service.com'),
    'api_key' => env('E_IMZO_API_KEY'),
];
```

**Note:** If an API key is provided, it will be automatically included in the `Authorization: Bearer {api_key}` header for all requests.

### Translations

Publish the translation files:

```bash
php artisan vendor:publish --tag="e-imzo-translations"
```

The package includes translations in English, Uzbek, and Russian. You can customize them or add your own translations. Translation files are located in `lang/vendor/e-imzo/`.

Available translation keys:
- `challenge_fail` - Challenge request failed
- `auth_fail` - Authentication failed
- `timestamp_fail` - Timestamp attachment failed
- `timestamp_reject` - Timestamp rejected
- `verify_fail` - Verification failed
- `invalid_json` - Invalid JSON response
- `invalid_base_url` - Invalid base URL configuration
- `service_unavailable` - Service unavailable

### Migration (Optional)

If you need to store E-IMZO related data, you can publish and run migrations:

```bash
php artisan vendor:publish --tag="e-imzo-migrations"
php artisan migrate
```

## Usage

### Using Facade

The easiest way to use the package is through the Laravel Facade:

```php
use Aslnbxrz\EImzo\Facades\EImzo;

// Get challenge
$challenge = EImzo::challenge();

if ($challenge instanceof \Aslnbxrz\EImzo\Data\ChallengeResponseData) {
    echo $challenge->challenge; // Challenge string
    echo $challenge->ttl;       // Time to live
} else {
    // It's an ErrorResponseData
    echo $challenge->message;
}
```

### Using Dependency Injection

```php
use Aslnbxrz\EImzo\EImzo;

class AuthController extends Controller
{
    public function __construct(
        private readonly EImzo $eImzo
    ) {}
    
    public function login(Request $request)
    {
        // Get challenge
        $challengeResult = $this->eImzo->challenge();
        
        if (!$challengeResult instanceof \Aslnbxrz\EImzo\Data\ChallengeResponseData) {
            return response()->json(['error' => $challengeResult->message], 400);
        }
        
        // Send challenge to client for signing...
        // After client signs the challenge, authenticate
        $pkcs7 = $request->input('pkcs7');
        $authResult = $this->eImzo->authenticate(
            pkcs7: $pkcs7,
            userIp: $request->ip(),
            host: $request->getHost()
        );
        
        if ($authResult instanceof \Aslnbxrz\EImzo\Data\AuthenticateResponseData) {
            // Authentication successful
            $certInfo = $authResult->subjectCertificateInfo;
            // Process certificate info...
        }
    }
}
```

### Using Container Resolution

```php
$eImzo = app(\Aslnbxrz\EImzo\EImzo::class);
$result = $eImzo->challenge();
```

## API Methods

### `challenge(): ChallengeResponseData|ErrorResponseData`

Get a challenge from the E-IMZO server for authentication.

```php
$result = EImzo::challenge();

if ($result instanceof \Aslnbxrz\EImzo\Data\ChallengeResponseData && $result->isSuccess()) {
    $challengeString = $result->challenge;
    $ttl = $result->ttl;
}
```

**Returns:**
- `ChallengeResponseData` on success (contains `status`, `challenge`, `ttl`, `message`)
- `ErrorResponseData` on failure (contains `status`, `message`)

**Throws:** `RequestException`, `FatalRequestException` on transport errors

---

### `authenticate(string $pkcs7, string $userIp, string $host): AuthenticateResponseData|ErrorResponseData`

Authenticate with a signed PKCS#7 challenge.

```php
$result = EImzo::authenticate(
    pkcs7: $signedChallenge,
    userIp: $request->ip(),
    host: $request->getHost()
);

if ($result instanceof \Aslnbxrz\EImzo\Data\AuthenticateResponseData && $result->isSuccess()) {
    $certInfo = $result->subjectCertificateInfo;
    // Process certificate information
}
```

**Parameters:**
- `$pkcs7` (string): Signed challenge in PKCS#7 format (base64 or PEM)
- `$userIp` (string): User's IP address
- `$host` (string): Request host for audit purposes

**Returns:**
- `AuthenticateResponseData` on success (contains `status`, `subjectCertificateInfo`, `message`)
- `ErrorResponseData` on failure

**Throws:** `RequestException`, `FatalRequestException` on transport errors

---

### `timestamp(string $pkcs7): TimestampResponseData|ErrorResponseData`

Attach a timestamp to a PKCS#7 signature.

```php
$result = EImzo::timestamp($pkcs7);

if ($result instanceof \Aslnbxrz\EImzo\Data\TimestampResponseData && $result->isSuccess()) {
    $timestampedPkcs7 = $result->pkcs7b64;
}
```

**Parameters:**
- `$pkcs7` (string): Raw PKCS#7 in base64 format

**Returns:**
- `TimestampResponseData` on success (contains `status`, `pkcs7b64`, `message`)
- `ErrorResponseData` on failure

**Throws:** `RequestException`, `FatalRequestException` on transport errors

---

### `verify(string $pkcs7wtst, ?string $data64 = null): VerifyResponseData|ErrorResponseData`

Verify a PKCS#7 signature (attached or detached mode).

```php
// Attached verification
$result = EImzo::verify($pkcs7WithTimestamp);

// Detached verification
$result = EImzo::verify(
    pkcs7wtst: $pkcs7WithTimestamp,
    data64: $originalDataBase64
);

if ($result instanceof \Aslnbxrz\EImzo\Data\VerifyResponseData && $result->isSuccess()) {
    $pkcs7Info = $result->pkcs7Info;
    // Process verification information
}
```

**Parameters:**
- `$pkcs7wtst` (string): PKCS#7 with timestamp (required)
- `$data64` (string|null): Original data in base64 for detached verification (optional)

**Returns:**
- `VerifyResponseData` on success (contains `status`, `pkcs7Info`, `message`)
- `ErrorResponseData` on failure

**Throws:** `RequestException`, `FatalRequestException` on transport errors

---

### `healthCheck(): bool`

Check if the E-IMZO service is healthy by attempting a challenge request.

```php
if (EImzo::healthCheck()) {
    // Service is healthy
} else {
    // Service is down or unreachable
}
```

**Returns:** `true` if service is healthy, `false` otherwise

## Response Data Objects

All methods return Laravel Data objects that extend `ResponseData`. Each response object has:

### Success Responses

- `ChallengeResponseData`: `status`, `challenge`, `ttl`, `message`
- `AuthenticateResponseData`: `status`, `subjectCertificateInfo`, `message`
- `TimestampResponseData`: `status`, `pkcs7b64`, `message`
- `VerifyResponseData`: `status`, `pkcs7Info`, `message`

### Error Responses

- `ErrorResponseData`: `status`, `message`

All success response objects have an `isSuccess()` method:

```php
if ($result->isSuccess()) {
    // Handle success
}
```

## Error Handling

The package uses Laravel Data objects for type-safe error handling:

```php
$result = EImzo::challenge();

if ($result instanceof \Aslnbxrz\EImzo\Data\ErrorResponseData) {
    // Handle error
    Log::error('E-IMZO Error', [
        'status' => $result->status,
        'message' => $result->message
    ]);
}
```

For transport-level errors (network issues, timeouts), the package throws Saloon exceptions:

```php
try {
    $result = EImzo::challenge();
} catch (\Saloon\Exceptions\Request\RequestException $e) {
    // Handle request exception
} catch (\Saloon\Exceptions\Request\FatalRequestException $e) {
    // Handle fatal exception
}
```

## Example: Complete Authentication Flow

```php
use Aslnbxrz\EImzo\Facades\EImzo;
use Aslnbxrz\EImzo\Data\ChallengeResponseData;
use Aslnbxrz\EImzo\Data\AuthenticateResponseData;
use Aslnbxrz\EImzo\Data\ErrorResponseData;

class EImzoAuthController extends Controller
{
    public function getChallenge()
    {
        $result = EImzo::challenge();
        
        if ($result instanceof ErrorResponseData) {
            return response()->json([
                'error' => $result->message
            ], 400);
        }
        
        return response()->json([
            'challenge' => $result->challenge,
            'ttl' => $result->ttl
        ]);
    }
    
    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'pkcs7' => 'required|string'
        ]);
        
        $result = EImzo::authenticate(
            pkcs7: $validated['pkcs7'],
            userIp: $request->ip(),
            host: $request->getHost()
        );
        
        if ($result instanceof ErrorResponseData) {
            return response()->json([
                'error' => $result->message
            ], 401);
        }
        
        // Authentication successful
        $user = $this->createOrUpdateUser($result->subjectCertificateInfo);
        
        return response()->json([
            'user' => $user,
            'certificate' => $result->subjectCertificateInfo
        ]);
    }
}
```

## Testing

Run the test suite:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [aslnbxrz](https://github.com/aslnbxrz)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.