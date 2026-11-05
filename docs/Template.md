# Template

[Back to the list of sections](../README.md)

You have the option of storing a template on the UniOne side and then using it by id, which is highly recommended for sending repetitive messages or high volume traffic. UniOne supports two different [template engines](https://docs.unione.io/en/template-engines).

PHP: `UniOne\Api\Template\TemplateApi`, `$client->template()`

| Method | Endpoint | Description |
|---|---|---|
| [`set()`](#set) | `POST /template/set.json` | Creates or updates an email template. |
| [`get()`](#get) | `POST /template/get.json` | Gets template properties by it's id. |
| [`list()`](#list) | `POST /template/list.json` | Returns the list of all or some templates of the account. |
| [`delete()`](#delete) | `POST /template/delete.json` | Deletes a template by id. |

## set

`POST /template/set.json` · [Documentation](https://docs.unione.io/en/web-api-ref#template-set)

Creates or updates an email template.

Restrictions:
* The maximum request size is 10 MB.
* The number of templates is limited to 10000.

### Request: `TemplateSetRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `template` | `TemplateObject` | yes | Object with all the template properties. |

### Response: `TemplateSetResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `template` | `TemplateObject` | Object with all the template properties. |

### Example

```php
<?php

use UniOne\Api\Template\Model\EditorType;
use UniOne\Api\Template\Model\TemplateObject;
use UniOne\Api\Template\Model\TemplateSetRequest;
use UniOne\Client\ApiException;
use UniOne\Common\Model\TemplateEngine;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->template()->set(new TemplateSetRequest(
        template: new TemplateObject(
            name: 'name',
            editorType: EditorType::Html,
            templateEngine: TemplateEngine::Velocity,
            subject: 'UniOne test email ',
            fromEmail: 'user@example.com',
            fromName: 'John Smith',
            replyTo: 'email@example.com',
            replyToName: 'John Smith',
        ),
    ));
    var_dump($response->template);
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## get

`POST /template/get.json` · [Documentation](https://docs.unione.io/en/web-api-ref#template-get)

Gets template properties by it's id.

### Request: `TemplateGetRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `id` | `string` | yes | Template id. |

### Response: `TemplateGetResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `template` | `TemplateObject` | Object with all the template properties. |

### Example

```php
<?php

use UniOne\Api\Template\Model\TemplateGetRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->template()->get(new TemplateGetRequest(
        id: '00000000-0000-0000-0000-000000000000',
    ));
    var_dump($response->template);
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## list

`POST /template/list.json` · [Documentation](https://docs.unione.io/en/web-api-ref#template-list)

Returns the list of all or some templates of the account.

You can iterate thru large list using "offset" and "limit" parameters. When less than "limit" templates are returned then the end of list is reached.

### Request: `TemplateListRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `limit` | `int` |  | Limits the number of templates returned, default is 50. |
| `offset` | `int` |  | Index of the first template in the list, starting with 0. |

### Response: `TemplateListResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `templates` | `list<TemplateListObject>` | Array of template objects. |

### Example

```php
<?php

use UniOne\Api\Template\Model\TemplateListRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->template()->list(new TemplateListRequest());
    var_dump($response->templates);
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## delete

`POST /template/delete.json` · [Documentation](https://docs.unione.io/en/web-api-ref#template-delete)

Deletes a template by id.

### Request: `TemplateDeleteRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `id` | `string` | yes | Template id. |

### Response: `SuccessResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |

### Example

```php
<?php

use UniOne\Api\Template\Model\TemplateDeleteRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->template()->delete(new TemplateDeleteRequest(
        id: '00000000-0000-0000-0000-000000000000',
    ));
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```
