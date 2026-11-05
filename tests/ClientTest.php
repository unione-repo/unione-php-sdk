<?php

declare(strict_types=1);

namespace UniOne\Tests;

use PHPUnit\Framework\TestCase;
use UniOne\Api\Email\Model\EmailSendRequest;
use UniOne\Api\Email\Model\EmailSendResponse;
use UniOne\Api\Email\Model\MessageObject;
use UniOne\Api\Email\Model\MessageObjectRecipientsItem as Recipient;
use UniOne\Client\ApiClient;
use UniOne\Client\ApiException;
use UniOne\Client\Configuration;
use UniOne\Common\Model\BodyObject as Body;
use UniOne\UniOne;
use UniOne\UniOneClient;

/**
 * The client against FakeTransport, the cURL transport against sockets on 127.0.0.1: the API is never called.
 */
final class ClientTest extends TestCase
{
    /** Base URL of the tests: a local address, never the default API host. */
    private const BASE_URL = 'http://127.0.0.1:9/api/v1';

    private const SEND_ERROR = ['status' => 'error', 'message' => 'Unknown recipient', 'code' => 204];

    private static function client(FakeTransport $transport, string $apiKey = 'test-key'): UniOneClient
    {
        return UniOne::client($apiKey, self::BASE_URL, $transport);
    }

    /**
     * email/send of the short form of the README.
     */
    private static function send(UniOneClient $client): EmailSendResponse
    {
        return $client->email()->send(new EmailSendRequest(new MessageObject(
            recipients: [Recipient::fromAddress('Alex <alex@example.com>')],
            body: Body::html('<p>Hello</p>'),
            subject: 'Your order',
            fromEmail: 'shop@example.com',
            fromName: 'Shop',
        )));
    }

    private function apiError(callable $call): ApiException
    {
        try {
            $call();
        } catch (ApiException $e) {
            return $e;
        }
        $this->fail('ApiException expected');
    }

    public function testSendEmail(): void
    {
        $transport = new FakeTransport([FakeTransport::json(200, [
            'status' => 'success',
            'job_id' => '1ZymBc-00041N-9X',
            'emails' => ['alex@example.com'],
            'failed_emails' => ['bad@example.com' => 'unsubscribed'],
        ])]);

        $response = self::send(self::client($transport));

        $this->assertCount(1, $transport->requests);
        $request = $transport->requests[0];
        $this->assertSame(self::BASE_URL . '/email/send.json?platform=php-sdk.2.3.0', $request['url']);
        $this->assertSame('php-sdk.2.3.0', ApiClient::PLATFORM);
        $this->assertSame('test-key', $request['headers']['X-API-KEY']);
        $this->assertSame('application/json', $request['headers']['Content-Type']);
        $this->assertEquals(['message' => [
            'recipients' => [['email' => 'alex@example.com', 'substitutions' => ['to_name' => 'Alex']]],
            'body' => ['html' => '<p>Hello</p>'],
            'subject' => 'Your order',
            'from_email' => 'shop@example.com',
            'from_name' => 'Shop',
        ]], \json_decode($request['body'], true));
        $this->assertSame('1ZymBc-00041N-9X', $response->jobId);
        $this->assertSame(['alex@example.com'], $response->emails);
        $this->assertSame(['bad@example.com' => 'unsubscribed'], $response->failedEmails);
    }

    public function testMethodWithoutBody(): void
    {
        $transport = new FakeTransport([FakeTransport::json(200, [
            'status' => 'success',
            'user_id' => 11344,
            'email' => 'user@example.com',
        ])]);

        $info = self::client($transport)->system()->info();

        $this->assertSame(self::BASE_URL . '/system/info.json?platform=php-sdk.2.3.0', $transport->requests[0]['url']);
        $this->assertSame('{}', $transport->requests[0]['body']);
        $this->assertSame(11344, $info->userId);
        $this->assertSame('user@example.com', $info->email);
    }

    public function testApiError(): void
    {
        $client = self::client(new FakeTransport([FakeTransport::json(400, self::SEND_ERROR)]));

        $e = $this->apiError(function () use ($client) {
            self::send($client);
        });

        $this->assertSame(ApiException::KIND_API, $e->getKind());
        $this->assertFalse($e->isRetryable());
        $this->assertSame(204, $e->getErrorCode());
        $this->assertSame(400, $e->getHttpStatus());
        $this->assertSame('email/send', $e->getMethod());
        $this->assertNotEmpty($e->getHint());
        $this->assertSame('Unknown recipient', $e->getApiMessage());
        $this->assertSame(self::SEND_ERROR, $e->getResponseBody());
        $this->assertStringStartsWith('email/send: Unknown recipient', $e->getMessage());
        $this->assertStringContainsString('(API error 204, HTTP 400)', $e->getMessage());
        $this->assertStringContainsString('Fix: ', $e->getMessage());
    }

    public function testRateLimitAndServerErrorsAreRetryable(): void
    {
        foreach ([429, 503] as $status) {
            $client = self::client(new FakeTransport([FakeTransport::json($status, [
                'status' => 'error', 'message' => 'Try later', 'code' => 999,
            ])]));

            $e = $this->apiError(function () use ($client) {
                $client->system()->info();
            });

            $this->assertSame(ApiException::KIND_API, $e->getKind());
            $this->assertSame($status, $e->getHttpStatus());
            $this->assertTrue($e->isRetryable(), "HTTP $status");
        }
    }

    public function testResponseThatIsNotJson(): void
    {
        $client = self::client(new FakeTransport([
            ['status' => 502, 'body' => '<html><body>502 Bad Gateway</body></html>'],
            ['status' => 200, 'body' => 'OK'],
        ]));

        $proxy = $this->apiError(function () use ($client) {
            $client->system()->info();
        });
        $this->assertSame(ApiException::KIND_INVALID_RESPONSE, $proxy->getKind());
        $this->assertSame(502, $proxy->getHttpStatus());
        $this->assertTrue($proxy->isRetryable());
        $this->assertStringStartsWith('system/info: ', $proxy->getMessage());
        $this->assertStringContainsString('502 Bad Gateway', $proxy->getMessage());

        $broken = $this->apiError(function () use ($client) {
            $client->system()->info();
        });
        $this->assertSame(ApiException::KIND_INVALID_RESPONSE, $broken->getKind());
        $this->assertSame(200, $broken->getHttpStatus());
        $this->assertFalse($broken->isRetryable());
    }

    public function testNetworkError(): void
    {
        // a port nobody listens on: the connection is refused
        $server = self::listen();
        $port = self::port($server);
        \fclose($server);

        $e = $this->apiError(function () use ($port) {
            UniOne::client('test-key', 'http://127.0.0.1:' . $port . '/api/v1', null, 2.0)->system()->info();
        });

        $this->assertSame(ApiException::KIND_NETWORK, $e->getKind());
        $this->assertTrue($e->isRetryable());
        $this->assertSame('system/info', $e->getMethod());
        $this->assertSame(0, $e->getHttpStatus());
        $this->assertStringStartsWith('system/info: HTTP request failed', $e->getMessage());
    }

    public function testTimeout(): void
    {
        // a server that accepts the connection and never answers
        $server = self::listen();
        $port = self::port($server);
        try {
            $e = $this->apiError(function () use ($port) {
                UniOne::client('test-key', 'http://127.0.0.1:' . $port . '/api/v1', null, 0.3)->system()->info();
            });
        } finally {
            \fclose($server);
        }

        $this->assertSame(ApiException::KIND_TIMEOUT, $e->getKind());
        $this->assertTrue($e->isRetryable());
        $this->assertStringStartsWith('system/info: HTTP request timed out', $e->getMessage());
    }

    public function testEmptyApiKey(): void
    {
        $transport = new FakeTransport();

        $e = $this->apiError(function () use ($transport) {
            self::client($transport, '')->system()->info();
        });

        $this->assertSame(ApiException::KIND_INVALID_REQUEST, $e->getKind());
        $this->assertFalse($e->isRetryable());
        $this->assertSame(0, $e->getHttpStatus());
        $this->assertStringStartsWith('system/info: the API key is empty', $e->getMessage());
        $this->assertCount(0, $transport->requests);
    }

    public function testRequiredFieldsAreCheckedBeforeSending(): void
    {
        $transport = new FakeTransport();
        $api = new ApiClient(new Configuration('test-key', self::BASE_URL), $transport);
        $message = ['body' => ['html' => '<p>Hello</p>'], 'subject' => 'Hello', 'from_email' => 'shop@example.com'];
        $cases = [
            ['email/send: message.recipients is required', $message],
            ['email/send: message.recipients is required', ['recipients' => []] + $message],
            ['email/send: message.recipients[0].email is required', ['recipients' => [['email' => '']]] + $message],
        ];

        foreach ($cases as [$expected, $body]) {
            $e = $this->apiError(function () use ($api, $body) {
                $api->call('/email/send.json', ['message' => $body]);
            });
            $this->assertSame(ApiException::KIND_INVALID_REQUEST, $e->getKind());
            $this->assertSame($expected, $e->getMessage());
        }
        $this->assertCount(0, $transport->requests);
    }

    public function testLongApiMessageIsCut(): void
    {
        $long = \str_repeat('x', 600);
        $client = self::client(new FakeTransport([
            FakeTransport::json(400, ['status' => 'error', 'message' => $long, 'code' => 204]),
        ]));

        $e = $this->apiError(function () use ($client) {
            $client->system()->info();
        });

        $this->assertSame($long, $e->getApiMessage());
        $this->assertStringContainsString(\str_repeat('x', 500) . '... (600 chars)', $e->getMessage());
        $this->assertStringNotContainsString(\str_repeat('x', 501), $e->getMessage());
    }

    public function testErrorCodeAsString(): void
    {
        $client = self::client(new FakeTransport([
            FakeTransport::json(400, ['status' => 'error', 'message' => 'Bad request', 'code' => '204']),
            FakeTransport::json(400, ['status' => 'error', 'message' => 'Bad request', 'code' => 'abc']),
        ]));
        $call = function () use ($client) {
            $client->system()->info();
        };

        $this->assertSame(204, $this->apiError($call)->getErrorCode());
        $e = $this->apiError($call);
        $this->assertNull($e->getErrorCode());
        $this->assertNull($e->getHint());
        $this->assertStringContainsString('(HTTP 400)', $e->getMessage());
    }

    public function testErrorOfCustomTransport(): void
    {
        $plain = new ApiException('connection reset');
        $timeout = new ApiException('took too long', 0, null, null, null, '', null, ApiException::KIND_TIMEOUT);
        $own = new ApiException('own error', 418, 7, null, null, 'custom/method', 'hint', ApiException::KIND_API);
        $client = self::client(new FakeTransport([$plain, $timeout, $own]));
        $call = function () use ($client) {
            $client->system()->info();
        };

        $e = $this->apiError($call);
        $this->assertSame(ApiException::KIND_NETWORK, $e->getKind());
        $this->assertTrue($e->isRetryable());
        $this->assertSame('system/info', $e->getMethod());
        $this->assertSame('system/info: connection reset', $e->getMessage());
        $this->assertSame($plain, $e->getPrevious());

        $e = $this->apiError($call);
        $this->assertSame(ApiException::KIND_TIMEOUT, $e->getKind());
        $this->assertSame('system/info: took too long', $e->getMessage());

        $this->assertSame($own, $this->apiError($call));
    }

    /**
     * @return resource a server socket on 127.0.0.1 (a free port)
     */
    private static function listen()
    {
        // a proxy of the environment must not take the requests to 127.0.0.1
        \putenv('no_proxy=127.0.0.1');
        \putenv('NO_PROXY=127.0.0.1');
        $server = \stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if (false === $server) {
            throw new \RuntimeException("can not listen on 127.0.0.1: $error");
        }

        return $server;
    }

    /**
     * @param resource $server
     */
    private static function port($server): int
    {
        $name = (string)\stream_socket_get_name($server, false);

        return (int)\substr($name, (int)\strrpos($name, ':') + 1);
    }
}
