<?php

declare(strict_types=1);
/* @var $this View */

/* @var $model PermissionForm */

use deadmantfa\yii2\rbac\forms\PermissionForm;
use yii\web\View;

$this->title = 'Add Permission';
$this->params['breadcrumbs'][] = ['label' => 'Permissions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['heading'] = 'Permissions';
$this->params['subheading'] = 'Add Permission';
?>

<div class="role-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

