<?php

declare(strict_types=1);

namespace UniOne\Client;

/**
 * Thrown when the API returns an error, the request fails or is invalid, or the response can not be processed.
 * The message names the API method and, for an API error, the code, the HTTP status and how to fix it:
 * "email/send: Unknown recipient (API error 204, HTTP 400). Fix: ... See ...#api-errors".
 */
class ApiException extends \RuntimeException
{
    /** The API returned an error (getErrorCode(), getHint()). */
    public const KIND_API = 'api';

    /** The request could not be sent or the response could not be received. */
    public const KIND_NETWORK = 'network';

    /** The request timed out. */
    public const KIND_TIMEOUT = 'timeout';

    /** The response is not the JSON of the API (a proxy page, a broken body). */
    public const KIND_INVALID_RESPONSE = 'invalid_response';

    /** Found before sending (an empty API key, a required field missing): no request was sent. */
    public const KIND_INVALID_REQUEST = 'invalid_request';

    /**
     * @param array<string, mixed>|null $responseBody
     */
    public function __construct(
        string $message,
        private readonly int $httpStatus = 0,
        private readonly ?int $errorCode = null,
        private readonly ?array $responseBody = null,
        ?\Throwable $previous = null,
        private readonly string $method = '',
        private readonly ?string $hint = null,
        private readonly string $kind = self::KIND_API,
        private readonly ?string $apiMessage = null,
    ) {
        parent::__construct($message, $errorCode ?? $httpStatus, $previous);
    }

    /**
     * HTTP status code of the response, 0 when the request was not sent.
     */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * API error code (the "code" field of the error response).
     */
    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, mixed>|null Decoded error response.
     */
    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }

    /**
     * API method of the request, e.g. "email/send"; empty when unknown.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * How to fix the API error (the errors table of the docs), null when unknown.
     */
    public function getHint(): ?string
    {
        return $this->hint;
    }

    /**
     * What failed: one of the KIND_* constants.
     */
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * The message of the API itself (the "message" field of the error response, in full), null when there was no
     * API response; getMessage() is the decorated text.
     */
    public function getApiMessage(): ?string
    {
        return $this->apiMessage;
    }

    /**
     * A repeat of the request may succeed: a network error, a timeout, HTTP 429 or 5xx.
     */
    public function isRetryable(): bool
    {
        if (self::KIND_NETWORK === $this->kind || self::KIND_TIMEOUT === $this->kind) {
            return true;
        }
        if (self::KIND_API === $this->kind || self::KIND_INVALID_RESPONSE === $this->kind) {
            return 429 === $this->httpStatus || $this->httpStatus >= 500;
        }

        return false;
    }
}
