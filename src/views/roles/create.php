<?php

declare(strict_types=1);
/* @var $this View */

/* @var $model RoleForm */

use deadmantfa\yii2\rbac\forms\RoleForm;
use yii\web\View;

$this->title = 'Add role';
$this->params['breadcrumbs'][] = ['label' => 'Permissions', 'url' => ['permissions/index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['heading'] = 'Permissions';
$this->params['subheading'] = 'Add Role';
?>

<div class="role-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

