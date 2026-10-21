# UniOne PHP SDK

[![PHP](https://img.shields.io/packagist/v/unione-repo/unione-php-sdk)](https://packagist.org/packages/unione-repo/unione-php-sdk) [![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

Official PHP library for the [UniOne](https://unione.io) transactional email API.

[API reference](https://docs.unione.io/en/web-api-ref) · Docs for LLMs: [llms.txt](https://docs.unione.io/llms.txt)

> **On Laravel or Symfony?** Install [`unione-repo/unione-laravel`](https://github.com/unione-repo/unione-laravel) or [`unione-repo/unione-symfony-bundle`](https://github.com/unione-repo/unione-symfony) instead: this SDK plus configuration, a mailer and a webhook route.

## Install

```bash
composer require unione-repo/unione-php-sdk
```

The client needs PHP 7.2+ with `ext-curl`, no other dependencies. A [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client (Guzzle, Symfony HttpClient, ...) is optional, see “HTTP client” below.

This is the 1.x line (branch `1.x`) for PHP 7.2. Newer runtimes: the 2.x line (branch `main`), the same API.

Get an API key in the [control panel](https://cp.unione.io/en/settings/security/api) and keep it in the environment, never in code:

```bash
export UNIONE_API_KEY="your-api-key"
```

## Send an email

```php
<?php

use UniOne\Api\Email\Model\EmailSendRequest;
use UniOne\Api\Email\Model\MessageObject;
use UniOne\Api\Email\Model\MessageObjectRecipientsItem as Recipient;
use UniOne\Common\Model\BodyObject as Body;
use UniOne\UniOne;

$client = UniOne::client(getenv('UNIONE_API_KEY'));

$message = (new MessageObject(
    [Recipient::fromAddress('Alex <alex@example.com>')],
    Body::html('<p>Hello, {{to_name}}! Your order has shipped.</p>'),
    'Your order'
))
    ->setFromEmail('shop@example.com')
    ->setFromName('Shop');

$response = $client->email()->send(new EmailSendRequest($message));

echo $response->getJobId(), PHP_EOL;
```

The sender address must be on a domain verified in your account. No domain yet? Send from the [sandbox domain](#no-domain-yet-the-test-sandbox-domain) in three steps.

## Contents

- [Recipes](#recipes): personalization · attachments · CC and BCC · scheduling · safe retries · errors · webhooks
- [No domain yet: the test sandbox domain](#no-domain-yet-the-test-sandbox-domain)
- [Going to production](#going-to-production)
- [Reference](#reference)
- [Help](#help)

## Recipes

Every recipe continues the example above: the client and its imports are there.

### Personalize for each recipient

Up to 500 recipients per call, each gets a separate copy with its own substitutions. Metadata of a recipient comes back in its webhook events.

```php
$message = (new MessageObject(
    [
        Recipient::fromAddress('Alex <alex@example.com>', ['order' => '1001'])->withMetadata(['user_id' => '42']),
        Recipient::fromAddress('Maria <maria@example.com>', ['order' => '1002'])->withMetadata(['user_id' => '43']),
    ],
    Body::html('<p>{{to_name}}, order #{{order}} has shipped.</p>'),
    'Order #{{order}}'
))
    ->setFromEmail('shop@example.com');

$client->email()->send(new EmailSendRequest($message));
```

### Attach a file

```php
use UniOne\Common\Model\AttachmentsArrayItem as Attachment;

$message = (new MessageObject(
    [Recipient::fromAddress('alex@example.com')],
    Body::html('<p>Your invoice is attached.</p>'),
    'Invoice'
))
    ->setFromEmail('shop@example.com')
    ->setAttachments([
        new Attachment(
            'application/pdf',
            'invoice.pdf',
            base64_encode(file_get_contents('invoice.pdf'))
        ),
    ]);

$client->email()->send(new EmailSendRequest($message));
```

### CC and BCC

Every address goes to `recipients` (each gets a copy); the visible ones also go to the headers. `audit@example.com` is in neither header: a BCC. The rules and limits: [CC and BCC](https://docs.unione.io/en/cc-and-bcc).

```php
use UniOne\Common\Model\HeadersObject;

$message = (new MessageObject(
    [
        Recipient::fromAddress('alex@example.com'),
        Recipient::fromAddress('boss@example.com'),
        Recipient::fromAddress('audit@example.com'),
    ],
    Body::html('<p>The report is attached.</p>'),
    'Weekly report'
))
    ->setFromEmail('shop@example.com')
    ->setHeaders((new HeadersObject())->setExtra(['To' => 'alex@example.com', 'CC' => 'boss@example.com']));

$client->email()->send(new EmailSendRequest($message));
```

### Schedule

`send_at` is UTC, up to 24 hours ahead.

```php
use UniOne\Api\Email\Model\MessageObjectOptions;

$sendAt = gmdate('Y-m-d H:i:s', time() + 3600);  // UTC, "YYYY-MM-DD hh:mm:ss"

$message = (new MessageObject(
    [Recipient::fromAddress('alex@example.com')],
    Body::text('See you tomorrow!'),
    'Reminder'
))
    ->setFromEmail('shop@example.com')
    ->setOptions((new MessageObjectOptions())->setSendAt($sendAt));

$client->email()->send(new EmailSendRequest($message));
```

### Retry safely

A repeat with the same `idempotence_key` within a minute is not sent twice: retry network errors, timeouts, 429 and 5xx with it.

```php
$message = (new MessageObject(
    [Recipient::fromAddress('alex@example.com')],
    Body::html('<p>Use this link to reset your password.</p>'),
    'Reset your password'
))
    ->setFromEmail('shop@example.com')
    ->setIdempotenceKey('password-reset-8f3a');

$client->email()->send(new EmailSendRequest($message));
```

### Handle errors

Recipients the API drops (unsubscribed, invalid, suppressed) are not an error: they are in `failed_emails` of the response, the rest still get the message.

```php
use UniOne\Client\ApiException;

try {
    $client->email()->send($request);
} catch (ApiException $e) {
    // HTTP status (0: network error or timeout), API error code, message
    echo "{$e->getHttpStatus()} {$e->getErrorCode()}: {$e->getMessage()}", PHP_EOL;
}
```

### Receive delivery events

Register your HTTPS endpoint once (`webhook/set`). Then check the signature of every notification (`auth`: MD5 of the raw body with the `auth` value replaced by your API key; a wrong one gets 401) and process the events.

```php
use UniOne\Api\Webhook\WebhookApi;

// $raw: the body of the POST request as received; returns the HTTP status to answer
function handleWebhook(string $raw): int
{
    $callback = WebhookApi::parseCallback($raw);
    $auth = (string) $callback->getAuth();
    // auth: MD5 of the raw body with the auth value replaced by your API key
    if (!hash_equals(md5(str_replace($auth, (string) getenv('UNIONE_API_KEY'), $raw)), $auth)) {
        return 401;
    }
    foreach ($callback->getEventsByUser() ?? [] as $user) {
        foreach ($user->getEvents() ?? [] as $event) {
            // email, status (delivered, opened, hard_bounced, spam, ...), job_id, metadata
        }
    }

    return 200;
}

http_response_code(handleWebhook(file_get_contents('php://input')));
```

> **Answer 200 within 3 seconds** and process the events in the background (a queue): otherwise UniOne repeats the request every 10 minutes for 24 hours (`retry_count` grows), and a handler that failed every request for at least 10 events of a day is turned inactive. The handler must take at least **5 parallel requests of up to 100 events each**; deduplicate, a repeat delivers the same events again.

## No domain yet: the test sandbox domain

Until your domain is verified, send from the **sandbox domain**: a test domain every account has, already verified, with DKIM and SPF set. It delivers only to addresses you confirm (the confirmation link comes by email, valid 48 hours), up to 100 emails a day. The sandbox domain is deleted 31 days after it was created, but you can get it again: the next `domain/get-sandbox-domain` call creates a new one (with a new name, so read it from the call, do not hardcode it).

```php
use UniOne\Api\ConfirmedEmail\Model\ConfirmedEmailRequest;

// 1. Your sandbox domain: created on the first call, the same one afterwards
$domain = $client->domain()->getSandboxDomain()->getDomain();

// 2. Confirm your inbox: UniOne sends a link, click it within 48 hours
$client->confirmedEmail()->create(new ConfirmedEmailRequest('you@example.com'));

// 3. Send from any address on the sandbox domain to the confirmed one
$message = (new MessageObject(
    [Recipient::fromAddress('you@example.com')],
    Body::html('<p>It works!</p>'),
    'Hello from UniOne'
))
    ->setFromEmail("noreply@{$domain}");

$client->email()->send(new EmailSendRequest($message));
```

<details>
<summary>Sandbox limits</summary>

- Sends only to confirmed addresses: up to 10 per account.
- Up to 100 emails a day.
- The sandbox domain expires after 31 days; call `domain/get-sandbox-domain` again to get a new one.
- Works only with the account API key, not a project key.
- Not listed in `domain/list`; the other `domain/*` methods do not apply to it.

The sandbox is for development and testing. Do not use a sandbox domain as the sender in production.

</details>

<details>
<summary>The same with curl</summary>

```bash
BASE=https://api.unione.io/en/transactional/api/v1

curl -s -X POST $BASE/domain/get-sandbox-domain.json \
  -H "Content-Type: application/json" -H "X-API-KEY: $UNIONE_API_KEY" -d '{}'

curl -s -X POST $BASE/confirmed-email/create.json \
  -H "Content-Type: application/json" -H "X-API-KEY: $UNIONE_API_KEY" \
  -d '{"email": "you@example.com"}'

curl -s -X POST $BASE/email/send.json \
  -H "Content-Type: application/json" -H "X-API-KEY: $UNIONE_API_KEY" \
  -d '{
        "message": {
          "recipients": [
            {
              "email": "you@example.com"
            }
          ],
          "from_email": "noreply@<sandbox-domain>",
          "subject": "Hello from UniOne",
          "body": {
            "html": "<p>It works!</p>"
          }
        }
      }'
```

</details>

## Going to production

**1. Verify your sending domain.** `domain/get-dns-records` returns the verification record, the DKIM key and the DMARC target; add the records at your DNS provider, then `domain/validate-verification-record` and `domain/validate-dkim`.

| Type | Host | Value | Where it comes from |
|---|---|---|---|
| TXT | `@` | `unione-validate-hash=…` | `verification-record` of the response, as is |
| TXT | `<selector>._domainkey` (the selector is on the domain page of the control panel, e.g. `us`) | `v=DKIM1; k=rsa; p=<dkim>` | `dkim` of the response after `v=DKIM1; k=rsa; p=` |
| TXT | `@` | `v=spf1 include:spf.unione.io ~all` | not in the response; add the include to your SPF record if you have one |
| CNAME | `_dmarc` | `<domain>.dmarc.…` | `dmarc` of the response (UniOne publishes the DMARC policy); keep your own DMARC TXT record instead if the domain has one |

**2. Make sending reliable.**

| | |
|---|---|
| Retries | `429` and `5xx` with backoff and the same `idempotence_key`; fix other errors instead of retrying |
| Limits | 500 recipients and 10 MB per request, attachments included: base64 makes a file about a third larger |
| Body | always `html` or `plaintext` (a template can supply it) |
| Delivery status | a webhook, not polling |
| Queue | send from a background job, not inside the web request |
| Config | `from_email` from config, so the sandbox → production switch needs no code change |
| Defaults | keep the unsubscribe footer and tracking; changing them needs UniOne support |

## Reference

### Sections

| Section | Methods | Description |
|---|---|---|
| [Email](docs/Email.md) | `send`, `subscribe` | This group of methods is related with email sending. |
| [EmailValidation](docs/EmailValidation.md) | `single` | Email validation methods. As a result of the check, you’ll get detailed information on email validity. It will help eliminate nonexistent emails, decrease the number of complaints, clean up the subscribers list and eventually enhance your sender reputation score. For example, you may add validation to a subscription form on your site to let users know they’ve just entered an invalid address. |
| [ConfirmedEmail](docs/ConfirmedEmail.md) | `create`, `list`, `delete` | Manage email addresses confirmed for sandbox-domain sending. These methods are available only with a main account API key. |
| [Template](docs/Template.md) | `set`, `get`, `list`, `delete` | You have the option of storing a template on the UniOne side and then using it by id, which is highly recommended for sending repetitive messages or high volume traffic. UniOne supports two different [template engines](https://docs.unione.io/en/template-engines). |
| [Webhook](docs/Webhook.md) | `set`, `get`, `list`, `delete` | UniOne lets you recieve notifications about email status changes or spam blocks. You provide an URL and every time a watched event occurs we will notify you by submitting a request with JSON data to this URL. |
| [Suppression](docs/Suppression.md) | `set`, `get`, `list`, `delete` | Methods for managing suppression lists. An email can be added to suppression list because of unsubscription, bounce or complain. |
| [Domain](docs/Domain.md) | `getDnsRecords`, `validateVerificationRecord`, `validateDkim`, `list`, `getSandboxDomain`, `delete` | You can use these methods to setup sender domains and check their statuses. They might be useful in case you want to automate adding domains for your own clients. |
| [EventDump](docs/EventDump.md) | `create`, `get`, `list`, `delete` | These methods are used to obtain a list of events for a given period of time. The data is stored for up to 45 days, depending on your tariff, i. e. you may fetch all event data starting from 00:00:00 31 days ago and up to current time. For custom tarifs with big sending volumes event dumps are available only for the last 7 days. |
| [Tag](docs/Tag.md) | `list`, `delete` | This family of methods is used to manage user-defined tags. Tags are used to categorize letters based on your own criteria, e. g. to create a distinction between marketing and transactional letters or group together all notifications for a particular order type. UniOne allows you to assign tags to all outgoing emails, and the tags you’ve set may then be used to filter and analyze delivery statistics. |
| [Project](docs/Project.md) | `create`, `update`, `list`, `delete` | Projects allow you to group several independent domains, webhooks, templates, and unsubscription lists together. It's very useful if you have your own clients and manage their data from a single UniOne account. Each project receives its own API key, and all API calls with that API key only deal with the data of that project. This way, you can assign a separate project for each client and be sure that they won't conflict; e.g. unsubscribing from one project won't affect another. |
| [System](docs/System.md) | `ping`, `info` | System operations. |
| [Obsolete](docs/Obsolete.md) | `unsubscribedSet`, `unsubscribedCheck`, `unsubscribedList` | Obsolete methods dealing with unsubscribes. We recommend using the more flexible [suppression](https://docs.unione.io/en/web-api-ref#suppression) methods instead. Note that you can't resubscribe an unsubscribed recipient using WebAPI, but you can send him an email with subscription link using email/subscribe method. |

### API host

```php
<?php

use UniOne\UniOne;
use UniOne\Client\Configuration;

$client = UniOne::client('YOUR_API_KEY', 'eu1.unione.io');
$client = UniOne::client('YOUR_API_KEY', Configuration::HOST_US1);
$client = UniOne::client('YOUR_API_KEY', 'https://api.unione.io/en/transactional/api/v1');
```

### HTTP client

```php
<?php

use UniOne\UniOne;
use UniOne\Client\Transport\Psr18Transport;

$factory = new GuzzleHttp\Psr7\HttpFactory();
$client = UniOne::client(
    'YOUR_API_KEY',
    null,
    new Psr18Transport(new GuzzleHttp\Client(), $factory, $factory)
);
```

### Models

Requests and responses are plain PHP classes: the required fields go to the constructor, the optional ones to the fluent setters (`->setName(...)`), getters read them (`->getName()`). Enums are classes with constants. `toArray()` gives the JSON body, `fromArray()` builds an object from decoded JSON. Empty objects and maps are sent as `{}`.

#### Additional fields

Some objects take keys beyond their named fields, e.g. any header in `HeadersObject`. They go to the `extra` map (name => value, `setExtra()` / `getExtra()`): `fromArray()` puts every key that is not a named field there, `toArray()` sends them next to the named fields (a named field wins over the same key in `extra`).

```php
<?php

use UniOne\Common\Model\HeadersObject;

$headers = (new HeadersObject())
    ->setExtra(['X-MyHeader' => 'some value', 'CC' => 'copy@example.com', 'List-Unsubscribe' => '<mailto:unsubscribe@example.com>']);
var_dump($headers->getExtra());
```

### Error handling

Every method throws `UniOne\Client\ApiException` when the API returns an error. `getErrorCode()` returns the [API error code](https://docs.unione.io/en/web-api-ref#api-errors), `getHttpStatus()` the HTTP status.

## Help

- [Integration guides](https://docs.unione.io/en/integrations)
- AI coding agents: [llms.txt](https://docs.unione.io/llms.txt), the [MCP server](https://unione.io/en/agents), skills: `npx skills add https://github.com/unione-repo/unione-skills.git`
- [Support](https://cp.unione.io/en/support) · [Issues](https://github.com/unione-repo/unione-php-sdk/issues)
- Contributing: [AGENTS.md](AGENTS.md) — the SDK is generated from the OpenAPI spec.
