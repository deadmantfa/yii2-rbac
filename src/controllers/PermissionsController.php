<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\controllers;

use deadmantfa\yii2\rbac\forms\PermissionForm;
use deadmantfa\yii2\rbac\forms\PermissionRelForm;
use deadmantfa\yii2\rbac\forms\ScanForm;
use deadmantfa\yii2\rbac\models\ItemSearch;
use deadmantfa\yii2\rbac\models\Permission;
use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * PermissionsController implements the CRUD actions for AuthItems model.
 */
class PermissionsController extends Controller
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
                    'remove-relation' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $searchModel = new ItemSearch();
        $dataProviderRoles = $searchModel->searchRoles(Yii::$app->request->queryParams);

        $dataProviderPermissions = $searchModel->searchPermissions(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProviderRoles' => $dataProviderRoles,
            'dataProviderPermissions' => $dataProviderPermissions,
        ]);
    }

    /**
     * @throws InvalidConfigException
     */
    public function actionCreate(): Response|array|string
    {
        $model = new PermissionForm();
        $model->scenario = $model::SCENARIO_CREATE;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Permission saved successfully.');

            return $this->redirect(['update', 'name' => $model->name]);
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    /**
     *
     * @throws NotFoundHttpException|InvalidConfigException
     */
    public function actionUpdate(string $name): Response|array|string
    {
        if (!$perm = Permission::find($name)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $model = new PermissionForm();
        $model->setPermission($perm);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Permission saved successfully.');

            return $this->redirect(['update', 'name' => $model->name]);
        }

        $relModel = new PermissionRelForm();
        return $this->render('update', [
            'model' => $model,
            'permission' => $perm,
            'relModel' => $relModel,
        ]);
    }

    /**
     * Delete a permission
     *
     *
     * @throws NotFoundHttpException
     */
    public function actionDelete(string $name): Response
    {
        if (!$perm = Permission::find($name)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        if (Yii::$app->authManager->remove($perm->getItem())) {
            Yii::$app->session->setFlash('success', 'Permission removed successfully.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Add relations to a permission
     *
     *
     * @throws NotFoundHttpException
     */
    public function actionAddRelation(string $name): Response
    {
        if (!$perm = Permission::find($name)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $model = new PermissionRelForm();
        if ($model->load(Yii::$app->request->post())) {
            $model->setPermission($perm);

            try {
                $newRelations = Yii::$app->request->post('PermissionRelForm')['relations'] ?? [];
                foreach ($newRelations as $newItem) {
                    if (!$model->validateNoHierarchyLoop($newItem)) {
                        Yii::$app->session->setFlash('warning', "Cannot add relation '$newItem' due to hierarchy loop.");
                        return $this->redirect(['update', 'name' => $name]);
                    }
                }

                if ($model->addRelations()) {
                    Yii::$app->session->setFlash('success', 'New relations added successfully.');
                } else {
                    $errors = $model->getFirstErrors();
                    Yii::$app->session->setFlash('warning', $errors ? reset($errors) : 'Some error occurred.');
                }
            } catch (Exception) {
                Yii::$app->session->setFlash('warning', 'Relations can\'t be added due to hierarchy loop or nesting issues.');
            }
        }

        return $this->redirect(['update', 'name' => $name]);
    }

    /**
     * Remove relation from permission
     *
     *
     * @throws NotFoundHttpException
     */
    public function actionRemoveRelation(string $name, string $item, string $scenario): Response
    {
        if (!$perm = Permission::find($name)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $model = new PermissionRelForm();
        $model->setScenario($scenario);
        $model->setPermission($perm);

        if ($model->removeRelation($item)) {
            Yii::$app->session->setFlash('success', 'Relations removed.');
        } else {
            Yii::$app->session->setFlash('warning', 'Some error occured.');
        }

        return $this->redirect(['update', 'name' => $name]);
    }

    /**
     * Scan routes and create missing permissions
     *
     * @throws \yii\base\Exception
     */
    public function actionScan(): Response|string
    {
        $model = new ScanForm();

        if ($model->load(Yii::$app->request->post()) && $actionRoutes = $model->scan()) {
            if ($inserted = $model->importPermissions($actionRoutes)) {
                Yii::$app->session->setFlash('success', 'Added ' . count($inserted) . ' permission(s).');

                if (defined('YII_DEBUG') && YII_DEBUG) {
                    $detailedMsg = implode('</li><li>', array_keys($inserted));
                    Yii::$app->session->setFlash(
                        'success',
                        "Added permissions: <ul><li>$detailedMsg</li></ul>"
                    );
                }
            } else {
                Yii::$app->session->setFlash('warning', 'No new permissions found.');
            }

            return $this->redirect(['index']);
        }

        return $this->render('scan', [
            'model' => $model,
        ]);
    }
}

