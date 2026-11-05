# Project

[Back to the list of sections](../README.md)

Projects allow you to group several independent domains, webhooks, templates, and unsubscription lists together. It's very useful if you have your own clients and manage their data from a single UniOne account. Each project receives its own API key, and all API calls with that API key only deal with the data of that project. This way, you can assign a separate project for each client and be sure that they won't conflict; e.g. unsubscribing from one project won't affect another.

Projects methods are disabled by default due to antispam measures. To enable them, [contact support](https://cp.unione.io/en/support).

Please note that sending domains are confirmed and used on per-project basis by default. But it's possible to allow projects to send emails from domains confirmed for main account - just [ask support](https://cp.unione.io/en/support) to enable it.

PHP: `UniOne\Api\Project\ProjectApi`, `$client->project()`

| Method | Endpoint | Description |
|---|---|---|
| [`create()`](#create) | `POST /project/create.json` | Creates a new project. |
| [`update()`](#update) | `POST /project/update.json` | Updates a project. |
| [`list()`](#list) | `POST /project/list.json` | Lists projects. |
| [`delete()`](#delete) | `POST /project/delete.json` | Deletes a project. |

## create

`POST /project/create.json` · [Documentation](https://docs.unione.io/en/web-api-ref#project-create)

Creates a new project.

### Request: `ProjectCreateRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `project` | `ProjectObject` | yes | Object with project properties. |

### Response: `ProjectCreateResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `project_id` | `string` | Unqiue project identifier, ASCII string up to 36 characters long. |
| `project_api_key` | `string` | API key of the project. You can use it instead of the user API key parameter in all methods except project/* methods. |

### Example

```php
<?php

use UniOne\Api\Project\Model\ProjectCreateRequest;
use UniOne\Api\Project\Model\ProjectObject;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->project()->create(new ProjectCreateRequest(
        (new ProjectObject('Project 1A'))
            ->setCountry('ES')
            ->setBackendDomainId(1234)
            ->setUnsubscribePageId(1)
    ));
    echo $response->getProjectId(), PHP_EOL;
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## update

`POST /project/update.json` · [Documentation](https://docs.unione.io/en/web-api-ref#project-update)

Updates a project.

### Request: `ProjectUpdateRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `project_id` | `string` |  | Unique project identifier. You should identify project to update either with project_id or with project_api_key field. But we recommend usage of project_id as a more secure option. |
| `project_api_key` | `string` |  | Optional project API key. You should identify project to update either with project_id or with project_api_key field. But we recommend usage of project_id as a more secure option. |
| `project` | `ProjectUpdateObject` | yes | Object with project properties. |

### Response: `ProjectUpdateResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `project_api_key` | `string` | API key of the project. Returned only if the project to update was identified by project_api_key. Not returned in case of updating by project_id. |

### Example

```php
<?php

use UniOne\Api\Project\Model\ProjectUpdateObject;
use UniOne\Api\Project\Model\ProjectUpdateRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->project()->update((new ProjectUpdateRequest(
        (new ProjectUpdateObject('Project 1A'))
            ->setCountry('ES')
            ->setBackendDomainId(1234)
            ->setUnsubscribePageId(1)
    ))
        ->setProjectId('1234567890')
        ->setProjectApiKey('your api_key'));
    echo $response->getProjectApiKey(), PHP_EOL;
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## list

`POST /project/list.json` · [Documentation](https://docs.unione.io/en/web-api-ref#project-list)

Lists projects.

### Request: `ProjectListRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `project_id` | `string` |  | Optional possibility to specify project_id to include only this project to list. |
| `project_api_key` | `string` |  | Optional possibility to specify project_api_key to include only this project to list. |

### Response: `ProjectListResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |
| `projects` | `list<ProjectListObject>` | Array of objects where each object represents one project. |

### Example

```php
<?php

use UniOne\Api\Project\Model\ProjectListRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->project()->list((new ProjectListRequest())
        ->setProjectId('1234567890')
        ->setProjectApiKey('your api_key'));
    var_dump($response->getProjects());
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```

## delete

`POST /project/delete.json` · [Documentation](https://docs.unione.io/en/web-api-ref#project-delete)

Deletes a project.

The project will be moved to the "trash bin" before being permanently deleted. You can restore the project by contacting support.

### Request: `ProjectDeleteRequest`

| Field | Type | Required | Description |
|---|---|---|---|
| `project_id` | `string` |  | Unique project identifier. You should identify project to delete either with project_id or with project_api_key field. We recommend usage of project_id as a more secure option. |
| `project_api_key` | `string` |  | Optional project API key. You should identify project to delete either with project_id or with project_api_key field. We recommend usage of project_id as a more secure option. |

### Response: `SuccessResponse`

| Field | Type | Description |
|---|---|---|
| `status` | `string` | "success" string (`success`) |

### Example

```php
<?php

use UniOne\Api\Project\Model\ProjectDeleteRequest;
use UniOne\Client\ApiException;
use UniOne\UniOne;

$client = UniOne::client('YOUR_API_KEY');

try {
    $response = $client->project()->delete((new ProjectDeleteRequest())
        ->setProjectId('1234567890')
        ->setProjectApiKey('your api_key'));
} catch (ApiException $e) {
    echo $e->getMessage(), PHP_EOL;
}
```
