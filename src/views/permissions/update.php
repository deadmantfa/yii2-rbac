<?php

declare(strict_types=1);

/* @var $this View */
/* @var $model PermissionForm */
/* @var $relModel PermissionRelForm */

/* @var $permission Permission */

use deadmantfa\yii2\rbac\forms\PermissionForm;
use deadmantfa\yii2\rbac\forms\PermissionRelForm;
use deadmantfa\yii2\rbac\models\Permission;
use yii\web\View;

$this->title = 'Update Permission';
$this->params['breadcrumbs'][] = ['label' => 'Permissions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['heading'] = 'Permissions';
$this->params['subheading'] = 'Update Permission';
?>

<div class="permissions-update">

    <?= $this->render('_form', [
        'model' => $model,
        'permission' => $permission,
        'relModel' => $relModel,
    ]) ?>

</div>

