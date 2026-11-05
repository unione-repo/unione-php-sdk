# System

[Back to the list of sections](../README.md)

System operations.

PHP: `UniOne\Api\System\SystemApi`, `$client->system()`

| Method | Endpoint | Description |
|---|---|---|
| [`ping()`](#ping) | `POST /system/ping.json` | Checks API key validity and returns user ID. |
| [`info()`](#info) | `POST /system/info.json` | Gets user or project info by API key. |

## ping

`POST /system/ping.json` · [Documentation](https://docs.unione.io/en/web-api-ref#system-ping)

Checks API key validity and returns user ID.

### Request

The method has no parameters.

### Response: `SystemPingResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `user_id` | `int` | Unique user identifier. |

### Example

```php
<?php

use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->system()->ping();
    echo $response->getUserId(), PHP_EOL;
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## info

`POST /system/info.json` · [Documentation](https://docs.unione.io/en/web-api-ref#system-info)

Gets user or project info by API key.

### Request

The method has no parameters.

### Response: `SystemInfoResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `user_id` | `int` | Unique user identifier. |
| `email` | `string` | Email of the user. |
| `project_id` | `string` | Unqiue project identifier, ASCII string up to 36 characters long. Present only if the API key used for request is the project API key. |
| `project_name` | `string` | Project name, unique for user account. Present only if the API key used for request is the project API key. |
| `project_accounting` | `ProjectAccountingObject` | Object with email counter properties. Present only if the API key used for request is the user API key. |
| `accounting` | `SystemInfoResponseAccounting` | Object with accounting period properties. Present only if the API key used for request is the user API key. |

### Example

```php
<?php

use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->system()->info();
    echo $response->getUserId(), PHP_EOL;
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```
