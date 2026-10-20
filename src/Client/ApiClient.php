<?php

declare(strict_types=1);

namespace UniOne\Client;

use UniOne\Client\Transport\CurlTransport;
use UniOne\Client\Transport\TransportInterface;

/**
 * Low level transport: every API method is a POST request with a JSON body that returns JSON.
 */
final class ApiClient
{
    /** The SDK and its version in the "platform" query parameter of every request. */
    public const PLATFORM = 'php-sdk.2.1.3';

    private readonly TransportInterface $transport;

    public function __construct(
        private readonly Configuration $config,
        ?TransportInterface $transport = null,
    ) {
        $this->transport = $transport ?? new CurlTransport();
    }

    /**
     * Calls an API method and returns the decoded response.
     *
     * @param string $path Method path, e.g. "/email/send.json".
     * @param array<string, mixed> $body Request body.
     *
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    public function call(string $path, array $body = []): array
    {
        try {
            $json = \json_encode($body === [] ? new \stdClass() : $body, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            throw new ApiException('Can not encode the request: ' . $e->getMessage(), 0, null, null, $e);
        }

        $url = $this->config->getBaseUrl() . '/' . \ltrim($path, '/')
            . '?' . \http_build_query(['platform' => $this->config->getPlatform()]);

        $response = $this->transport->post($url, [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => $this->config->getUserAgent(),
            'X-API-KEY' => $this->config->getApiKey(),
        ], $json, $this->config->getTimeout());

        $status = $response['status'];
        $raw = $response['body'];

        try {
            $data = \json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ApiException(\sprintf('Invalid JSON in response (HTTP %d): %s', $status, \substr($raw, 0, 500)), $status, null, null, $e);
        }
        if (!\is_array($data)) {
            throw new ApiException(\sprintf('Unexpected response (HTTP %d): %s', $status, \substr($raw, 0, 500)), $status);
        }

        if ($status >= 400 || ($data['status'] ?? null) === 'error') {
            throw new ApiException(
                (string)($data['message'] ?? 'API error'),
                $status,
                isset($data['code']) ? (int)$data['code'] : null,
                $data,
            );
        }

        return $data;
    }
}
