<?php

declare(strict_types=1);

/* @var $this View */
/* @var $model RoleForm */

/* @var $role Role */

use deadmantfa\yii2\rbac\forms\RoleForm;
use deadmantfa\yii2\rbac\models\Role;
use yii\web\View;

$this->title = 'Update role';
$this->params['breadcrumbs'][] = ['label' => 'Permissions', 'url' => ['permissions/index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['heading'] = 'Permissions';
$this->params['subheading'] = 'Update Role';
?>

<div class="update">

    <?= $this->render('_form', [
        'model' => $model,
        'role' => $role,
    ]) ?>

</div>

