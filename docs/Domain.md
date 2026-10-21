# Domain

[Back to the list of sections](../README.md)

You can use these methods to setup sender domains and check their statuses. They might be useful in case you want to automate adding domains for your own clients.

To add new sender domain thru API you should take following steps:
- Get DNS records with [domain/get-dns-records](https://docs.unione.io/en/web-api-ref#domain-get-dns-records) method and add them to your DNS provider.
- Every 5-10 minutes call [domain/list](https://docs.unione.io/en/web-api-ref#domain-list) method for your domain (or several domains) and check verification-record.status and dkim.status in the response. You can call it more often if you like, but please not more than once per minute.
- If verification-record.status="unconfirmed" then you should call [domain/validate-verification-record](https://docs.unione.io/en/web-api-ref#domain-validate-verification-record) to trigger verification record validation.
- If dkim.status="inactive" then start DKIM checking by calling [domain/validate-dkim](https://docs.unione.io/en/web-api-ref#domain-validate-dkim) (UniOne itself checks  DKIM every 30 minutes also)
- When you get verification-record.status="confirmed" and dkim.status="active" for the domain then you can stop checking it's status with domain/list and start using it as a sender domain.

PHP: `UniOne\Api\Domain\DomainApi`, `$client->domain()`

| Method | Endpoint | Description |
|---|---|---|
| [`getDnsRecords()`](#getdnsrecords) | `POST /domain/get-dns-records.json` | Returns info on domain DNS setup for using with UniOne. |
| [`validateVerificationRecord()`](#validateverificationrecord) | `POST /domain/validate-verification-record.json` | Triggers verification record validation for domain. |
| [`validateDkim()`](#validatedkim) | `POST /domain/validate-dkim.json` | Validates DKIM record for domain. |
| [`list()`](#list) | `POST /domain/list.json` | Returns a list of all registered domains for user or project, together with statuses of last validations. |
| [`getSandboxDomain()`](#getsandboxdomain) | `POST /domain/get-sandbox-domain.json` | Creates a sandbox domain for the account or returns the existing one. |
| [`delete()`](#delete) | `POST /domain/delete.json` | Deletes a domain. Attention: you should use project's API key to delete a project's domain. |

## getDnsRecords

`POST /domain/get-dns-records.json` · [Documentation](https://docs.unione.io/en/web-api-ref#domain-get-dns-records)

Returns info on domain DNS setup for using with UniOne.

In order to send emails you have to confirm that you own the domain that you are going to send emails from and setup DKIM signature for the domain. This method allows you to register your domain in UniOne and generate verification record and DKIM key, or return previously generated ones if domain is already registered.
You should add then DNS records like this to all of the domains you want to confirm:

`@             IN TXT "unione-validate-hash=483bb362ebdbeedd755cfb1d4d661"`

`us._domainkey IN TXT "v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDo7"`

`@             IN TXT  "v=spf1 include:spf.unione.io ~all"`

`_dmarc        IN CNAME "example.com.dmarc.unione.io."`

- First record value should contain "verification-record" field returned by get-dns-records method "as is".
- Second record value should contain "dkim" field value after "v=DKIM1; k=rsa; p=". Mind the name of this record! "us._domainkey." prefix is important for DKIM selector of UniOne to work properly.
- Third record contains optional, but very recommended SPF entry. It is not returned by the method. Probably you already have one, then just add append include:spf.unione.io right after v=spf1.
- Fourth record is a CNAME of the "_dmarc" host to the "dmarc" field value: UniOne then publishes the DMARC policy of the domain. If the domain already has its own DMARC TXT record, keep it instead.

Syntax requirements of different DNS providers may differ:
- sometimes it's necesarry to put full domain name instead of @ sign.
- some providers require add TTL value (like 3600)
- some providers don't require quotes for enclosing values
Please check requirements of your provider and advice your users to do it.

### Request: `DomainGetDnsRecordsRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `domain` | `string` | yes | Domain to get DNS records for. |

### Response: `DomainGetDnsRecordsResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `domain` | `string` | Domain to get DNS records for. |
| `verification-record` | `string` | Record to be added "as is" to verify ownership of this domain. |
| `dkim` | `string` | DKIM signature for the domain. This property contains only the key part, you should prepend it with "v=DKIM1; k=rsa; p=" for the TXT record to be valid. |
| `dmarc` | `string` | Target of the CNAME record for the "_dmarc" host of the domain. |

### Example

```php
<?php

use UniOne\Api\Domain\Model\DomainGetDnsRecordsRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->domain()->getDnsRecords(new DomainGetDnsRecordsRequest('example.com'));
    echo $response->getDomain(), PHP_EOL;
} catch (ApiException $e) {
    echo $e->getErrorCode(), ': ', $e->getMessage(), PHP_EOL;
}
```

## validateVerificationRecord

`POST /domain/validate-verification-record.json` · [Documentation](https://docs.unione.io/en/web-api-ref#domain-validate-verification-record)

Triggers verification record validation for domain.

If you get 200 OK then it means the validation process started succesfully, but it doesn't mean the verification record is valid. You should check later verification-record.status field returned by [domain/list](https://docs.unione.io/en/web-api-ref#domain-list) method to get the result. Please read more [here](https://docs.unione.io/en/web-api-ref#domain).

### Request: `DomainValidateVerificationRecordRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `domain` | `string` | yes | Domain to validate verification record for. |

### Response: `DomainValidateVerificationRecordResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `message` | `string` | Debug message. |

### Example

```php
<?php

use UniOne\Api\Domain\Model\DomainValidateVerificationRecordRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->domain()->validateVerificationRecord(new DomainValidateVerificationRecordRequest('example.com'));
    echo $response->getMessage(), PHP_EOL;
} catch (ApiException $e) {
    echo $e->getErrorCode(), ': ', $e->getMessage(), PHP_EOL;
}
```

## validateDkim

`POST /domain/validate-dkim.json` · [Documentation](https://docs.unione.io/en/web-api-ref#domain-validate-dkim)

Validates DKIM record for domain.

If you get 200 OK then it means the validation process started succesfully, but it doesn't mean the DKIM record is valid. You should check later dkim.status field returned by [domain/list](https://docs.unione.io/en/web-api-ref#domain-list) method to get the result. Please read more [here](https://docs.unione.io/en/web-api-ref#domain).

### Request: `DomainValidateDkimRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `domain` | `string` | yes | Domain to validate DKIM record for. |

### Response: `DomainValidateDkimResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `message` | `string` | Debug message. |

### Example

```php
<?php

use UniOne\Api\Domain\Model\DomainValidateDkimRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->domain()->validateDkim(new DomainValidateDkimRequest('example.com'));
    echo $response->getMessage(), PHP_EOL;
} catch (ApiException $e) {
    echo $e->getErrorCode(), ': ', $e->getMessage(), PHP_EOL;
}
```

## list

`POST /domain/list.json` · [Documentation](https://docs.unione.io/en/web-api-ref#domain-list)

Returns a list of all registered domains for user or project, together with statuses of last validations.

Returns last status of ownership validation and DKIM validation for each domain. Read more [here](https://docs.unione.io/en/web-api-ref#domain).

### Request: `DomainListRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `domain` | `string` |  | Optional domain name if you want to get the status of single domain only. |
| `limit` | `int` |  | Limits the number of domains returned. Default is 50. |
| `offset` | `int` |  | Index of the first domain in the list, starting with 0. |

### Response: `DomainListResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `domains` | `list<DomainListResponseDomainsItem>` | Array of objects describing domains. |

### Example

```php
<?php

use UniOne\Api\Domain\Model\DomainListRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->domain()->list((new DomainListRequest())
        ->setDomain('example.com'));
    var_dump($response->getDomains());
} catch (ApiException $e) {
    echo $e->getErrorCode(), ': ', $e->getMessage(), PHP_EOL;
}
```

## getSandboxDomain

`POST /domain/get-sandbox-domain.json` · [Documentation](https://docs.unione.io/en/web-api-ref#domain-get-sandbox-domain)

Creates a sandbox domain for the account or returns the existing one.

Returns an account sandbox domain that is ready for use in the `from_email` field of [email/send](https://docs.unione.io/en/web-api-ref#email-send). If the account has no sandbox domain, UniOne creates one and returns it. Repeated calls return the same domain.

The sandbox domain is removed 31 days after it was created; the next call creates a new one. Up to 100 emails a day can be sent from it (the default limit of the account).

The method is available only with an account API key. Project API keys cannot create sandbox domains.

### Request

The method has no parameters.

### Response: `DomainGetSandboxDomainResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `domain` | `string` | Sandbox domain name. |

### Example

```php
<?php

use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->domain()->getSandboxDomain();
    echo $response->getDomain(), PHP_EOL;
} catch (ApiException $e) {
    echo $e->getErrorCode(), ': ', $e->getMessage(), PHP_EOL;
}
```

## delete

`POST /domain/delete.json` · [Documentation](https://docs.unione.io/en/web-api-ref#domain-delete)

Deletes a domain. Attention: you should use project's API key to delete a project's domain.

Deletes a domain.

### Request: `DomainDeleteRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `domain` | `string` | yes | Domain name to delete. |

### Response: `DomainDeleteResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `message` | `string` | Domain deleted |

### Example

```php
<?php

use UniOne\Api\Domain\Model\DomainDeleteRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->domain()->delete(new DomainDeleteRequest('example.com'));
    echo $response->getMessage(), PHP_EOL;
} catch (ApiException $e) {
    echo $e->getErrorCode(), ': ', $e->getMessage(), PHP_EOL;
}
```
