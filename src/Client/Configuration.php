<?php

declare(strict_types=1);

namespace UniOne\Client;

/**
 * API connection settings.
 */
final class Configuration
{
    /**
     * UniOne API-server instance
     */
    public const SERVER_DEFAULT = 'https://api.unione.io/en/transactional/api/v1';

    public const DEFAULT_BASE_URL = self::SERVER_DEFAULT;

    /** API host api.unione.io. */
    public const HOST_API = 'api.unione.io';

    /** API host eu1.unione.io. */
    public const HOST_EU1 = 'eu1.unione.io';

    /** API host us1.unione.io. */
    public const HOST_US1 = 'us1.unione.io';

    /** Path of the API on every host. */
    public const BASE_PATH = '/en/transactional/api/v1';

    /** @var string */
    private $apiKey;

    /** @var string */
    private $baseUrl;

    /** @var float */
    private $timeout;

    /** @var string */
    private $userAgent;

    /** @var string|null */
    private $platform;

    /**
     * @param string $apiKey User or project API key, sent in the X-API-KEY header.
     * @param string $baseUrl Base URL of the API, see the SERVER_* constants.
     * @param float $timeout Request timeout in seconds.
     * @param string $userAgent
     * @param string|null $platform Package that uses the SDK and its version, e.g. "laravel.1.0.0": goes to the "platform" query parameter before the SDK one.
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = self::DEFAULT_BASE_URL,
        float $timeout = 30.0,
        string $userAgent = 'UniOne-php-client/1.2.3',
        ?string $platform = null
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
        $this->userAgent = $userAgent;
        $this->platform = $platform;
    }

    /**
     * Base URL for an API host: "eu1.unione.io" -> "https://eu1.unione.io" . BASE_PATH; a full URL is kept as is.
     */
    public static function baseUrlFor(string $host): string
    {
        if (false !== \strpos($host, '://')) {
            return \rtrim($host, '/');
        }

        return 'https://' . \trim($host, '/') . self::BASE_PATH;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return \rtrim($this->baseUrl, '/');
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    /**
     * The "platform" query parameter: "php-sdk.1.0.0" or, with an integration, "laravel.1.0.0,php-sdk.1.0.0".
     */
    public function getPlatform(): string
    {
        return null === $this->platform || '' === $this->platform
            ? ApiClient::PLATFORM
            : $this->platform . ',' . ApiClient::PLATFORM;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }
}
