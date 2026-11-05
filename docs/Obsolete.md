# Obsolete

[Back to the list of sections](../README.md)

Obsolete methods dealing with unsubscribes. We recommend using the more flexible [suppression](https://docs.unione.io/en/web-api-ref#suppression) methods instead. Note that you can't resubscribe an unsubscribed recipient using WebAPI, but you can send him an email with subscription link using email/subscribe method.

PHP: `UniOne\Api\Obsolete\ObsoleteApi`, `$client->obsolete()`

| Method | Endpoint | Description |
|---|---|---|
| [`unsubscribedSet()`](#unsubscribedset) | `POST /unsubscribed/set.json` | Registers a new unsubscribed email. |
| [`unsubscribedCheck()`](#unsubscribedcheck) | `POST /unsubscribed/check.json` | Checks if an email is unsubscribed. |
| [`unsubscribedList()`](#unsubscribedlist) | `POST /unsubscribed/list.json` | Returns a list of all unsubscribed emails since provided date. |

## unsubscribedSet

`POST /unsubscribed/set.json` · [Documentation](https://docs.unione.io/en/web-api-ref#unsubscribed-set)

Registers a new unsubscribed email.

After registering an email as unsubscribed all future attempts to send a message to this email will be rejected. Please note that there's no way to remove an email from unsubscribed thru the API. But you can send an email with a link to resubscribe using [email/subscribe](https://docs.unione.io/en/web-api-ref#email-subscribe) method or remove "unsubscribed" status manually on [Email Search page](https://cp.unione.io/en/user/email) in control panel (but only a limited number of unsubscribed email per day can be removed).

### Request: `ObsoleteUnsubscribedSetRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `address` | `string` | yes | Email to be unsubscribed. |

### Response: `ObsoleteUnsubscribedSetResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `address` | `string` | The unsubscribed email. |
| `message` | `ObsoleteUnsubscribedSetMessage` | Unsubscription process details:<br><br>- "unsubscribed" if email has just been unsubscribed,<br>- "was inactive" if it was inactive due some reason,<br>- "was unsubscribed" if it already was unsubscribed before the call,<br>- "added and unsubscribed" if it's a new email without status history and it was added and then unsubscribed immediately. (`unsubscribed`, `was inactive`, `was unsubscribed`, `added and unsubscribed`) |

### Example

```php
<?php

use UniOne\Api\Obsolete\Model\ObsoleteUnsubscribedSetRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->obsolete()->unsubscribedSet(new ObsoleteUnsubscribedSetRequest(
        address: 'email@example.com',
    ));
    echo $response->address, PHP_EOL;
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## unsubscribedCheck

`POST /unsubscribed/check.json` · [Documentation](https://docs.unione.io/en/web-api-ref#unsubscribed-check)

Checks if an email is unsubscribed.

### Request: `ObsoleteUnsubscribedCheckRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `address` | `string` | yes | Email to check. |

### Response: `ObsoleteUnsubscribedCheckResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `address` | `string` | Email that was checked. |
| `is_unsubscribed` | `bool` | true if unsubscribed, false if not. |

### Example

```php
<?php

use UniOne\Api\Obsolete\Model\ObsoleteUnsubscribedCheckRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->obsolete()->unsubscribedCheck(new ObsoleteUnsubscribedCheckRequest(
        address: 'email@example.com',
    ));
    echo $response->address, PHP_EOL;
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## unsubscribedList

`POST /unsubscribed/list.json` · [Documentation](https://docs.unione.io/en/web-api-ref#unsubscribed-list)

Returns a list of all unsubscribed emails since provided date.

### Request: `ObsoleteUnsubscribedListRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `date_from` | `string` |  | Date in the format YYYY-MM-DD to get all unsubscribed emails from the "date_from" to the present day. If the parameter is not specified, the default period is today (from 00:00 UTC). |

### Response: `ObsoleteUnsubscribedListResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `unsubscribed` | `list<ObsoleteUnsubscribedListResponseUnsubscribedItem>` | Array of object describing unsubscribed emails. |

### Example

```php
<?php

use UniOne\Api\Obsolete\Model\ObsoleteUnsubscribedListRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->obsolete()->unsubscribedList(new ObsoleteUnsubscribedListRequest(
        dateFrom: '2020-10-14',
    ));
    var_dump($response->unsubscribed);
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```
