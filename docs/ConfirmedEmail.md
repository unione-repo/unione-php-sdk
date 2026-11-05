# ConfirmedEmail

[Back to the list of sections](../README.md)

Manage email addresses confirmed for sandbox-domain sending. These methods are available only with a main account API key.

PHP: `UniOne\Api\ConfirmedEmail\ConfirmedEmailApi`, `$client->confirmedEmail()`

| Method | Endpoint | Description |
|---|---|---|
| [`create()`](#create) | `POST /confirmed-email/create.json` | Adds an email address and sends a confirmation link. |
| [`list()`](#list) | `POST /confirmed-email/list.json` | Lists confirmed and unconfirmed email addresses for the account. |
| [`delete()`](#delete) | `POST /confirmed-email/delete.json` | Deletes an email address from the account's confirmed email list. |

## create

`POST /confirmed-email/create.json` · [Documentation](https://docs.unione.io/en/web-api-ref#confirmed-email-create)

Adds an email address and sends a confirmation link.

### Request: `ConfirmedEmailRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `email` | `string` | yes | Email address to add or delete. |

### Response: `ConfirmedEmailCreateResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `confirmed_email` | `ConfirmedEmail` |  |

### Example

```php
<?php

use UniOne\Api\ConfirmedEmail\Model\ConfirmedEmailRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->confirmedEmail()->create(new ConfirmedEmailRequest(
        email: 'dev@example.com',
    ));
    var_dump($response->confirmedEmail);
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## list

`POST /confirmed-email/list.json` · [Documentation](https://docs.unione.io/en/web-api-ref#confirmed-email-list)

Lists confirmed and unconfirmed email addresses for the account.

### Request

The method has no parameters.

### Response: `ConfirmedEmailListResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `confirmed_emails` | `list<ConfirmedEmail>` |  |

### Example

```php
<?php

use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->confirmedEmail()->list();
    var_dump($response->confirmedEmails);
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## delete

`POST /confirmed-email/delete.json` · [Documentation](https://docs.unione.io/en/web-api-ref#confirmed-email-delete)

Deletes an email address from the account's confirmed email list.

### Request: `ConfirmedEmailRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `email` | `string` | yes | Email address to add or delete. |

### Response: `ConfirmedEmailDeleteResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `message` | `string` |  |

### Example

```php
<?php

use UniOne\Api\ConfirmedEmail\Model\ConfirmedEmailRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->confirmedEmail()->delete(new ConfirmedEmailRequest(
        email: 'dev@example.com',
    ));
    echo $response->message, PHP_EOL;
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```
