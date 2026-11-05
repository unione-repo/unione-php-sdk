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
    public const PLATFORM = 'php-sdk.2.3.0';

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
        $method = ErrorInfo::method($path);
        if ('' === $this->config->getApiKey()) {
            throw self::invalidRequest($method, 'the API key is empty: pass the API key of the user or of the project to the client');
        }
        try {
            $json = \json_encode($body === [] ? new \stdClass() : $body, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            throw self::invalidRequest($method, 'can not encode the request: ' . $e->getMessage(), $e);
        }
        $missing = ErrorInfo::missing($method, \json_decode($json, true));
        if (null !== $missing) {
            throw self::invalidRequest($method, $missing . ' is required');
        }

        $url = $this->config->getBaseUrl() . '/' . \ltrim($path, '/')
            . '?' . \http_build_query(['platform' => $this->config->getPlatform()]);

        try {
            $response = $this->transport->post($url, [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => $this->config->getUserAgent(),
                'X-API-KEY' => $this->config->getApiKey(),
            ], $json, $this->config->getTimeout());
        } catch (ApiException $e) {
            throw self::transportError($method, $e);
        }

        $status = $response['status'];
        $raw = $response['body'];

        try {
            $data = \json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ApiException(\sprintf('%s: invalid JSON in response (HTTP %d): %s', $method, $status, ErrorInfo::quote($raw)),
                $status, null, null, $e, $method, null, ApiException::KIND_INVALID_RESPONSE);
        }
        if (!\is_array($data)) {
            throw new ApiException(\sprintf('%s: unexpected response (HTTP %d): %s', $method, $status, ErrorInfo::quote($raw)),
                $status, null, null, null, $method, null, ApiException::KIND_INVALID_RESPONSE);
        }

        if ($status >= 400 || ($data['status'] ?? null) === 'error') {
            $code = ErrorInfo::code($data['code'] ?? null);
            $message = \is_scalar($data['message'] ?? null) ? (string)$data['message'] : '';
            throw new ApiException(
                ErrorInfo::apiMessage($method, $message, $status, $code),
                $status,
                $code,
                $data,
                null,
                $method,
                ErrorInfo::hint($code),
                ApiException::KIND_API,
                '' === $message ? null : $message
            );
        }

        return $data;
    }

    /**
     * An ApiException of the transport: one with a method is thrown as it is, else its data is kept, the message gets
     * the method and a plain one (kind api, no error code, no HTTP status) of a custom transport is a network error.
     */
    private static function transportError(string $method, ApiException $e): ApiException
    {
        if ('' !== $e->getMethod()) {
            return $e;
        }
        $kind = ApiException::KIND_API === $e->getKind() && null === $e->getErrorCode() && 0 === $e->getHttpStatus()
            ? ApiException::KIND_NETWORK : $e->getKind();
        $message = 0 === \strpos($e->getMessage(), $method . ': ') ? $e->getMessage() : $method . ': ' . $e->getMessage();

        return new ApiException($message, $e->getHttpStatus(), $e->getErrorCode(), $e->getResponseBody(), $e, $method,
            $e->getHint(), $kind, $e->getApiMessage());
    }

    private static function invalidRequest(string $method, string $message, ?\Throwable $previous = null): ApiException
    {
        return new ApiException($method . ': ' . $message, 0, null, null, $previous, $method, null, ApiException::KIND_INVALID_REQUEST);
    }
}
