<?php

declare(strict_types=1);

/* @var $this View */
/* @var $model RoleForm */

/* @var $role Role */

use deadmantfa\yii2\rbac\assets\RbacAssetBundle;
use deadmantfa\yii2\rbac\forms\ItemForm;
use deadmantfa\yii2\rbac\forms\RoleForm;
use deadmantfa\yii2\rbac\models\Role;
use deadmantfa\yii2\rbac\widgets\RbacActiveForm;
use yii\helpers\Html;
use yii\web\View;

RbacAssetBundle::register($this);
?>

<?php $form = RbacActiveForm::begin([]); ?>
<div id="justcoded-role-form" class="role-form panel box card">
    <div class="panel-header box-header card-header with-border">
        <h3 class="box-title card-title">Role</h3>
    </div>
    <div class="panel-body box-body card-body">
        <?= $form->field($model, 'name')->textInput([
            'maxlength' => true,
            'readonly' => $model->scenario != ItemForm::SCENARIO_CREATE
        ]) ?>

        <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'childRoles')
            ->inline()
            ->checkboxList(array_diff(Role::getList(), [$model->name]), [
                'value' => $model->childRoles,
            ])
        ?>

        <?= $this->render('_permissions', [
            'model' => $model,
            'form' => $form,
        ]) ?>
    </div>
    <div class="panel-footer box-footer card-footer text-right">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?> &nbsp;
        <?php if (!empty($role)) : ?>
            <?= Html::a(
                'delete',
                ['delete', 'name' => $model->name],
                [
                    'class' => 'text-danger',
                    'data' => [
                        'confirm' => 'Are you sure you want to delete this item?',
                        'method' => 'post',
                    ],
                ]
            ) ?>
        <?php endif; ?>
    </div>

</div>
<?php $form::end(); ?>
