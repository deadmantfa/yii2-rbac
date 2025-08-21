<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\controllers;

use deadmantfa\yii2\rbac\forms\RoleForm;
use deadmantfa\yii2\rbac\models\Role;
use Exception;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * RolesController implements the CRUD actions for AuthItems model.
 */
class RolesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Create form/action
     *
     * @throws Exception
     */
    public function actionCreate(): Response|string
    {
        $model = new RoleForm();
        $model->scenario = $model::SCENARIO_CREATE;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Role saved successfully.');

            return $this->redirect(['update', 'name' => $model->name]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Update form/action
     *
     *
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function actionUpdate(string $name): Response|array|string
    {
        $role = Role::find($name);
        if (!$role instanceof Role) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $model = new RoleForm();
        $model->setRole($role);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Role saved successfully.');

            return $this->redirect(['update', 'name' => $model->name]);
        }

        return $this->render('update', [
            'model' => $model,
            'role' => $role,
        ]);
    }

    /**
     * Delete action
     *
     *
     * @throws NotFoundHttpException
     */
    public function actionDelete(string $name): Response
    {
        $role = Role::find($name);
        if (!$role instanceof Role) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (Yii::$app->authManager->remove($role->getItem())) {
            Yii::$app->session->setFlash('success', 'Role removed successfully.');
        }

        return $this->redirect(['permissions/index']);
    }
}

