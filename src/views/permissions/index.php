<?php

declare(strict_types=1);

use deadmantfa\yii2\rbac\models\ItemSearch;
use deadmantfa\yii2\rbac\models\Role;
use deadmantfa\yii2\rbac\widgets\RbacGridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel deadmantfa\yii2\rbac\models\ItemSearch */
/* @var $dataProviderPermissions yii\data\ActiveDataProvider */
/* @var $dataProviderRoles yii\data\ActiveDataProvider */

$this->title = 'Permissions';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="permission-index">
    <div class="row">
        <div class="col-md-6">

            <div class="panel box">
                <div class="panel-header box-header with-border">
                    <h3 class="box-title">Roles
                        &nbsp;
                        <?= Html::a('Add Role', ['roles/create'], ['class' => 'btn btn-xs btn-sm btn-success']); ?>
                    </h3>
                </div>
                <div class="panel-body box-body">
                    <?= RbacGridView::widget([
                        'dataProvider' => $dataProviderRoles,
                        'filterModel' => $searchModel,
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],
                            [
                                'header' => 'Role',
                                'format' => 'raw',
                                'filter' => Html::activeTextInput($searchModel, 'roleName', ['class' => 'form-control']),
                                'value' => function ($data) {
                                    return Html::a($data->name, ['roles/update', 'name' => $data->name])
                                        . '<br>' . Html::encode($data->description);
                                },
                            ],
                            [
                                'header' => 'Permissions',
                                'headerOptions' => ['class' => 'col-md-2'],
                                'contentOptions' => ['class' => 'text-center'],
                                'value' => function ($data) {
                                    return count(Yii::$app->authManager->getPermissionsByRole($data->name));
                                },
                            ],
                            [
                                'header' => 'Inherit',
                                'value' => function ($data) {
                                    return implode(', ', ItemSearch::getInherit($data->name));
                                },
                            ]
                        ],
                    ]); ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel box">
                <div class="panel-header box-header with-border">
                    <h3 class="box-title">Permissions
                        &nbsp;
                        <?= Html::a('Add Permission', ['permissions/create'], ['class' => 'btn btn-xs btn-sm btn-success']); ?>
                        <?= Html::a('Scan Routes', ['permissions/scan'], ['class' => 'btn btn-xs btn-sm btn-default']); ?>
                    </h3>
                </div>
                <div class="panel-body box-body">
                    <?= RbacGridView::widget([
                        'dataProvider' => $dataProviderPermissions,
                        'filterModel' => $searchModel,
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],
                            [
                                'header' => 'Permission',
                                'format' => 'html',
                                'filter' => Html::activeTextInput($searchModel, 'permName', ['class' => 'form-control']),
                                'value' => function ($data) {
                                    return Html::a($data->name, ['permissions/update', 'name' => $data->name]);
                                }
                            ],
                            [
                                'attribute' => 'description',
                            ],
                            [
                                'header' => 'Roles',
                                'format' => 'html',
                                'headerOptions' => ['class' => 'col-md-2'],
                                'filter' => Html::activeDropDownList($searchModel, 'permRole', Role::getList(),
                                    ['class' => 'form-control', 'prompt' => 'Any']
                                ),
                                'value' => function ($data) {
                                    return implode(', ', ItemSearch::getRoleByPermission($data->name));
                                }
                            ]
                        ],
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>