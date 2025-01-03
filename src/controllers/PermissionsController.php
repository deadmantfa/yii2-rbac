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

    /**
     * @return string
     */
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
     * @return array|string|Response
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
     * @param string $name
     *
     * @return array|string|Response
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
     * @param string $name
     *
     * @return Response
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
     * @param string $name
     *
     * @return Response
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
            //TODO make it no possible to choose loop created items
            try {
                if ($model->addRelations()) {
                    Yii::$app->session->setFlash('success', 'New relations added successfully.');
                } else {
                    $errors = $model->getFirstErrors();
                    Yii::$app->session->setFlash('warning', $errors ? reset($errors) : 'Some error occured.');
                }
            } catch (Exception) {
                Yii::$app->session->setFlash('warning', 'Relations can\'t be added because of hierarchy loop or impossible nesting.');
            }
        }

        return $this->redirect(['update', 'name' => $name]);
    }

    /**
     * Remove relation from permission
     *
     * @param string $name
     * @param string $item
     * @param string $scenario
     *
     * @return Response
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
     * @return string|Response
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

