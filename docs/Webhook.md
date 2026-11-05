# Webhook

[Back to the list of sections](../README.md)

UniOne lets you recieve notifications about email status changes or spam blocks. You provide an URL and every time a watched event occurs we will notify you by submitting a request with JSON data to this URL.

You can configure the format of webhook calls (JSON or gzipped JSON), which events you want to be notified of, whether or not you let us groups several events in one request, do you want to receive extra details like SMTP answer or recipient's user agent, etc.

Please note that your webhook handler should be able to process at least 5 parallel requests, up to 100 events each (but you can configure the maximum number of parallel connections up to 100, which is recommended).

If the URL of your webhook handler is not available (the response is not HTTP 200 OK within 3 seconds timeout), the system will repeat requests each 10 minutes during 24 hours, passing extra parameter retry_count. If there were at least 10 events during last 24 hours and all the webhook requests about these events have failed then we consider webhook handler broken and change it's status to inactive automatically. A notification about that is sent to an email [configured in control panel](https://cp.unione.io/en/user/alerting/technical/).

The format of webhook request and details of each event are described [here](https://docs.unione.io/en/web-api-ref#callback-format).

PHP: `UniOne\Api\Webhook\WebhookApi`, `$client->webhook()`

| Method | Endpoint | Description |
|---|---|---|
| [`set()`](#set) | `POST /webhook/set.json` | Sets or edits a [webhook](https://docs.unione.io/en/web-api-ref#webhook), i.e. an event notification handler. |
| [`get()`](#get) | `POST /webhook/get.json` | Gets properties of a [webhook](https://docs.unione.io/en/web-api-ref#webhook). |
| [`list()`](#list) | `POST /webhook/list.json` | List all or some [webhooks](https://docs.unione.io/en/web-api-ref#webhook) (event notification handlers) of a user or a project. |
| [`delete()`](#delete) | `POST /webhook/delete.json` | Deletes an event notification handler |

## set

`POST /webhook/set.json` · [Documentation](https://docs.unione.io/en/web-api-ref#webhook-set)

Sets or edits a [webhook](https://docs.unione.io/en/web-api-ref#webhook), i.e. an event notification handler.

### Request: `WebhookSetRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `url` | `string` | yes | URL that will receive the notification when an event occurs. The URL should be unique for a user or a project. Only ASCII characters are supported for now in URL, please convert to [Punycode](https://en.wikipedia.org/wiki/Punycode) if you need to use non-ASCII characters. |
| `status` | `WebhookStatus` |  | Webhook status, "active" by default. "disabled" means that webhook has been disabled by the user, "stopped" means that webhook has been stopped by the system after 24h of failed calls (with minimum of 10 distinct events). (`active`, `disabled`, `stopped`) |
| `event_format` | `WebhookEventFormat` |  | Notification format. "json_post"(default) or "json_post_gzip". (`json_post`, `json_post_gzip`) |
| `delivery_info` | `int` |  | Shall detailed delivery info be returned(1) or not(0). When it's 1, the information about SMTP response and internal delivery status is returned for "hard_bounced" and "soft_bounced" email statuses; the user agent, recipient IP address, detected geo fields, device info detected from the user agent (device type and brand, OS name, browser or email client name) and traffic_type (human, machine, protected or unknown) are returned for "opened" and "clicked" statuses; URL is returned for "clicked" status. For "delivered", "soft_bounced" and "hard_bounced" statuses UniOne may also return sender_ip, the IP address of the SMTP server that sent the message, if available. (`0`, `1`) |
| `single_event` | `int` |  | 0=several event notifications can be reported in single webhook call, 1=only single event can be reported in one call (not recommended). (`0`, `1`) |
| `max_parallel` | `int` |  | Maximum quantity of permitted parallel queries to your server. The more your server can handle - the better. |
| `events` | `WebhookEventsObject` |  | Object containing events to notify of. |

### Response: `WebhookSetResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `object` | `WebhookObject` | Object with all the webhook properties. |

### Example

```php
<?php

use UniOne\Api\Webhook\Model\WebhookSetRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->webhook()->set(new WebhookSetRequest(
        url: 'https://yourhost.example.com/unione-webhook',
    ));
    var_dump($response->object);
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## get

`POST /webhook/get.json` · [Documentation](https://docs.unione.io/en/web-api-ref#webhook-get)

Gets properties of a [webhook](https://docs.unione.io/en/web-api-ref#webhook).

### Request: `WebhookGetRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `url` | `string` | yes | Webhook URL. |

### Response: `WebhookGetResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `object` | `WebhookObject` | Object with all the webhook properties. |

### Example

```php
<?php

use UniOne\Api\Webhook\Model\WebhookGetRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->webhook()->get(new WebhookGetRequest(
        url: 'https://example.com/webhook',
    ));
    var_dump($response->object);
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## list

`POST /webhook/list.json` · [Documentation](https://docs.unione.io/en/web-api-ref#webhook-list)

List all or some [webhooks](https://docs.unione.io/en/web-api-ref#webhook) (event notification handlers) of a user or a project.

### Request: `WebhookListRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `limit` | `int` |  | Limits the number of webhooks returned. 50 is recommended. |
| `offset` | `int` |  | Index of the first webhook in the list, starting with 0. |

### Response: `WebhookListResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `objects` | `list<WebhookObject>` | Array of webhooks objects. |

### Example

```php
<?php

use UniOne\Api\Webhook\Model\WebhookListRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->webhook()->list(new WebhookListRequest());
    var_dump($response->objects);
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## delete

`POST /webhook/delete.json` · [Documentation](https://docs.unione.io/en/web-api-ref#webhook-delete)

Deletes an event notification handler

You can also temporarily deactivate webhook without deleting it by setting it's status to "disabled" in [webhook/set](https://docs.unione.io/en/web-api-ref#webhook-set) method.

### Request: `WebhookDeleteRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `url` | `string` | yes | Webhook URL. |

### Response: `SuccessResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |

### Example

```php
<?php

use UniOne\Api\Webhook\Model\WebhookDeleteRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->webhook()->delete(new WebhookDeleteRequest(
        url: 'https://example.com/webhook',
    ));
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## Callbacks: parseCallback

Webhook callback format description.

Parse an incoming webhook request:

```php
<?php

use UniOne\Api\Webhook\WebhookApi;

$callback = WebhookApi::parseCallback(file_get_contents('php://input'));
var_dump($callback);
```

| Field | Type | Description |
|---|---|---|
| `auth` | `string` | MD5-hash of the message body, in which the value "auth" is replaced by api key of the user/project; with this field the recipient of the notification can both authenticate and verify the notification integrity |
| `events_by_user` | `list<WebhookCallbackEventsByUserItem>` | Array with only one element, containing events of a user/project. |
