# Yii 2 JustCoded RBAC Extension (Forked)

[![Yii2](https://avatars0.githubusercontent.com/u/993323)](https://github.com/yiisoft)

This is a fork of the Yii 2 RBAC extension by JustCoded. It extends the RBAC Manager with a route-based access control
system, offering additional features and compatibility with modern PHP versions.

---

## Features

### Pre-defined Roles and Permissions

The extension provides the following pre-defined roles and permissions:

**Permissions:**

- **`*` (master permission)**: A parent permission for all other permissions.
- **`administer`**: Permission to check access to the admin panel.

**Roles:**

- **`Guest`**: Not authenticated users.
- **`Authenticated`**: Authenticated users (add this to your users manually).
- **`Administrator`**: Users with `administer` permission, granting access to the admin panel.
- **`Master`**: Superuser role with access to everything via `*` permission.

---

### Routes Scanner

The extension includes a feature to scan your project files and automatically import permissions for:

- **Controller-wide permissions**: `{controller->uniqueId}/*`
- **Action-specific permissions**: `{controller->uniqueId}/{action->id}`

You can create or assign roles and permissions to configure your application's high-level access control.

---

### Route Access Filter

Easily restrict access to specific parts of your site based on roles or permissions. The extension provides a filter
similar to Yii's `AccessControl`, enabling route-based permissions checks. If access is denied, a `403 Forbidden` error
is triggered.

---

### GUI for Managing Roles and Permissions

A simple GUI is included to manage roles and permissions directly from the application.

> **Note:** The GUI is in alpha. Avoid sharing access to this interface with end-users.

---

## Installation

Install the extension via Composer:

```bash
composer require deadmantfa/yii2-rbac
```

Alternatively, add the following to your `composer.json`:

```json
"deadmantfa/yii2-rbac": "*"
```

---

## Configuration

### Component Setup

Add the RBAC module and `authManager` configuration in your application:

```php
'modules' => [
    'rbac' => [
        'class' => 'deadmantfa\yii2\rbac\Module',
    ],
],
'components' => [
    'authManager' => [
        'class' => 'deadmantfa\yii2\rbac\components\DbManager',
        //'class' => 'deadmantfa\yii2\rbac\components\PhpManager',
    ],
],
```

---

### Bootstrap 4 Themes Support

By default, the views use Bootstrap 3 via `yii2-bootstrap`. For Bootstrap 4 support, update the container configuration:

```php
'container' => [
    'definitions' => [
        'deadmantfa\yii2\rbac\widgets\RbacGridView' => [
            'class' => \app\modules\admin\widgets\RbacGridView::class,
        ],
        'deadmantfa\yii2\rbac\widgets\RbacActiveForm' => [
            'class' => \yii\bootstrap4\ActiveForm::class,
        ],
    ],
],
```

> **Note:** Add `yiisoft/yii2-bootstrap4` to your `composer.json`.

---

### Basic RBAC Configuration

Follow
the [official Yii 2 RBAC documentation](https://www.yiiframework.com/doc-2.0/guide-security-authorization.html#configuring-rbac)
to configure RBAC storage (e.g., create necessary files or database tables).

For `DbManager`, initialize the database tables with the following migration command:

```bash
yii migrate --migrationPath=@yii/rbac/migrations
```

---

### Initialize Base Roles

Run the following commands to set up default roles and permissions:

```bash
# Initialize base roles and permissions
php yii rbac/init

# Assign the master role to a user (replace 1 with the user ID)
php yii rbac/assign-master 1

# Scan application routes for permissions
php yii rbac/scan
```

For **Advanced Template**:

```bash
php yii rbac/scan -p='@vendor/deadmantfa/yii2-rbac' -b='rbac/'
```

For **Basic Template**:

```bash
php yii rbac/scan -p='@vendor/deadmantfa/yii2-rbac' -b='admin/rbac/'
```

---

## Usage

### GUI Interface

Access the RBAC GUI by navigating to the module's configured route. Use the GUI to manage roles and permissions.

> **Note:** The role-permission selector is a temporary solution and may not display a proper tree structure. This will
> be addressed in future updates.

---

### Route Access Filter

Use the `RouteAccessControl` filter to enforce route-based access control. The filter checks permissions during each
request and throws a `403 Forbidden` error for unauthorized routes.

#### Per Controller

```php
public function behaviors()
{
    return [
        'routeAccess' => [
            'class' => 'deadmantfa\yii2\rbac\filters\RouteAccessControl',
        ],
    ];
}
```

#### Globally

```php
'as routeAccess' => [
    'class' => 'deadmantfa\yii2\rbac\filters\RouteAccessControl',
    'allowActions' => [
        'site/*',
    ],
    'allowRegexp' => '/(gii)/i', // Optional
],
```

---

## Example Project

You can see an example of this RBAC extension in action in
the [Yii2 Starter Kit](https://github.com/justcoded/yii2-starter).

---
