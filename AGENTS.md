# AGENTS.md

UniOne PHP SDK — client for the UniOne transactional email Web API (Web API 1.90). How to integrate UniOne into an application comes first; the last section is about changing this package.

## Integrating UniOne

**On one of these frameworks, install its package instead of this SDK:** it brings the SDK and plugs it into the framework (configuration, client in the container, mailer, webhooks).

| Framework | Package | Guide |
|---|---|---|
| Laravel | `unione-repo/unione-laravel` | [AGENTS.md](https://github.com/unione-repo/unione-laravel/blob/HEAD/AGENTS.md) |
| Symfony | `unione-repo/unione-symfony-bundle` | [AGENTS.md](https://github.com/unione-repo/unione-symfony/blob/HEAD/AGENTS.md) |

### Install

```bash
composer require unione-repo/unione-php-sdk
```

Requirements: PHP 8.1+. The API key goes to `UNIONE_API_KEY`.

### Send an email

```php
<?php

use UniOne\Api\Email\Model\EmailSendRequest;
use UniOne\Api\Email\Model\MessageObject;
use UniOne\Api\Email\Model\MessageObjectRecipientsItem as Recipient;
use UniOne\Common\Model\BodyObject as Body;
use UniOne\UniOne;

$client = UniOne::client(getenv('UNIONE_API_KEY'));

$response = $client->email()->send(new EmailSendRequest(new MessageObject(
    recipients: [Recipient::fromAddress('Alex <alex@example.com>')],
    body: Body::html('<p>Hello, {{to_name}}! Your order has shipped.</p>'),
    subject: 'Your order',
    fromEmail: 'shop@example.com',
    fromName: 'Shop',
)));

echo $response->jobId, PHP_EOL;
```

Personalization, attachments, CC/BCC, scheduling, safe retries, error handling, the webhook handler and the sandbox flow: the recipes of [README.md](README.md#recipes).

### Rules for generated code

- Server-side only. Read the API key from `UNIONE_API_KEY`; never put it in browser or mobile code, never commit it.
- Build the recipients and the body with the short forms of the SDK (the recipient from `"Name <email>"`, the name goes to `to_name`; per-recipient metadata; the HTML / text body), as in "Send an email" and the README recipes, not with hand-built nested objects.
- Base URL `https://api.unione.io/en/transactional/api/v1` (an account registered on another host uses that one: `eu1.unione.io`, `us1.unione.io`); every method is POST with a JSON body; auth header `X-API-KEY`. The SDK does all of it.
- First email without DNS: `domain/get-sandbox-domain` → `confirmed-email/create` (the user clicks the link within 48 hours) → `email/send` from `noreply@<sandbox-domain>` to the confirmed address. Account API key only; up to 10 confirmed addresses, 100 emails a day. The domain is deleted 31 days after it was created: the next `domain/get-sandbox-domain` call creates a new one with another name, so read the name from the response, never hardcode it.
- Never ship a sandbox domain as the production sender (it is not in `domain/list`); read `from_email` from config. Production needs a verified domain: `domain/get-dns-records` → DNS records (verification TXT as is, DKIM TXT `v=DKIM1; k=rsa; p=<dkim>` on `<selector>._domainkey`, CNAME `_dmarc` → the `dmarc` field; SPF `include:spf.unione.io` is not in the response) → `domain/validate-verification-record`, `domain/validate-dkim` → `domain/list`.
- Set `message.idempotence_key` on retryable sends; retry network errors, `429` and `5xx` with backoff, not other errors unchanged. Up to 500 recipients and 10 MB per request with the attachments in base64 (a third larger than the files); always `body.html` or `body.plaintext`.
- Delivery events via webhook (`webhook/set`), not polling. Check the `auth` field of every notification (MD5 of the raw request body with the `auth` value replaced by the API key) and answer 401 to a wrong one. Answer 200 within 3 seconds and process the events from a queue, otherwise the request is repeated every 10 minutes for 24 hours; take at least 5 parallel requests of up to 100 events each; deduplicate.
- Keep the default unsubscribe and tracking behaviour; changes go through UniOne support.
- Do not invent methods or fields. Check [docs/](docs/) of this package, the [API reference](https://docs.unione.io/en/web-api-ref) or [llms.txt](https://docs.unione.io/llms.txt).

### Errors

Every method throws `UniOne\Client\ApiException` when the request fails: the HTTP status (0 for a network error or a timeout), the [API error code](https://docs.unione.io/en/web-api-ref#api-errors) and the message.

```php
// $client created as above
try {
    $client->system()->ping();
} catch (\UniOne\Client\ApiException $e) {
    // getHttpStatus() is 0 for a network error or a timeout
    error_log($e->getHttpStatus() . ' ' . $e->getErrorCode() . ' ' . $e->getMessage());
}
```

### Reference

- Methods and models of every section: [Email](docs/Email.md), [EmailValidation](docs/EmailValidation.md), [ConfirmedEmail](docs/ConfirmedEmail.md), [Template](docs/Template.md), [Webhook](docs/Webhook.md), [Suppression](docs/Suppression.md), [Domain](docs/Domain.md), [EventDump](docs/EventDump.md), [Tag](docs/Tag.md), [Project](docs/Project.md), [System](docs/System.md), [Obsolete](docs/Obsolete.md)
- [API reference](https://docs.unione.io/en/web-api-ref)
- [Full API reference for LLMs](https://docs.unione.io/llms-full.txt)

## Working on this repository

- **Generated code.** The whole package is generated by the UniOne team from the UniOne Web API OpenAPI spec (Web API 1.90): sources, docs, README.md and this file. Do not hand-edit it: the next release replaces the content of this repository. Report problems and requests in the issues of this repository (https://github.com/unione-repo/unione-php-sdk).
- **Build and check:**

  ```bash
  composer validate
  composer install
  ```

- **Source label.** Every request carries the query parameter `platform=php-sdk.2.2.4`, added in one place, the shared request client (`ApiClient`); a package built on this SDK puts its own label in front: `<package>.<version>,php-sdk.2.2.4`.
- **Tests.** Before a release the generator builds and lints the package and runs every example of docs/ and of this file against a mock of the API: no real API key, no real sends.
- **Release.** The version (2.2.4 in composer.json) and the tag `v2.2.4` come from the generator; README.md states the Web API version the release was generated from.
- **Secrets.** Never commit API keys; the examples read `UNIONE_API_KEY` from the environment.
