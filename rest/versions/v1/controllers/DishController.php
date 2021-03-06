<?php
namespace rest\versions\v1\controllers;

use Yii;
use yii\rest\Controller;
use common\models\Dish;
use yii\helpers\ArrayHelper;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use rest\models\search\DishSearch;
use yii\data\ActiveDataProvider;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class DishController extends Controller
{
    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'optional' => ['create', 'delete'],
            'authMethods' => [
                HttpBearerAuth::className(),
            ],
        ];
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'only' => [],
            'rules' => [
                [
                    'actions' => ['create', 'delete', 'update', 'view', 'dishes', 'chef-dishes', 'index'],
                    'allow' => true,
                    'roles' => ['chef'],
                ],
            ],
        ];
        return $behaviors;
    }

    public function actionCreate() {
        $dish = new Dish(['scenario' => Dish::SCENARIO_CREATE]);
        $bodyParams = Yii::$app->getRequest()->getBodyParams();
        $user = Yii::$app->user->id;
        $dish->load($bodyParams, '');
        if ($dish->validate()) {
            $dish->chefId = $user;
            $dish->save();
            Yii::$app->response->statusCode = 201;
            return [
                'dishId' => $dish->id,
            ];
        } else {
            Yii::$app->response->statusCode = 422;
            $response['error']['message'] = current($dish->getFirstErrors()) ?? null;

            return $response;
        }
    }
    
    public function actionUpdate($id) {
        $dish = Dish::findOne($id);
        if (empty($dish)) {
            Yii::$app->response->statusCode = 404;
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $dish->setScenario(Dish::SCENARIO_UPDATE);
        $dish->actionUserId = Yii::$app->user->id;
        $bodyParams = Yii::$app->getRequest()->getBodyParams();
        $dish->load($bodyParams, '');
        if ($dish->validate()) {
            $dish->save();
        } else {
            Yii::$app->response->statusCode = 422;
            $response['error']['message'] = current($dish->getFirstErrors()) ?? null;

            return $response;
        }
    }

    public function actionDelete() {
        $dish = new Dish(['scenario' => Dish::SCENARIO_DELETE]);
        $bodyParams = Yii::$app->getRequest()->getBodyParams();
        if ($dish->load($bodyParams, '') && $dish->validate()) {
            $dish = Dish::findOne($bodyParams['id']);
            $dish->delete();
        } else {
            Yii::$app->response->statusCode = 422;
            $response['error']['message'] = current($dish->getFirstErrors()) ?? null;

            return $response;
        }
    }
    
    public function actionView($id) {
        $dish = Dish::findOne($id);
        if (empty($dish)) {
            Yii::$app->response->statusCode = 404;
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        return
                ArrayHelper::toArray($dish, [
                    Dish::class => [
                        'id' => 'id',
                        'chefId' => 'chefId',
                        'name' => 'name',
                        'price' => 'price'
                    ]
        ]);
    }

    public function actionDishes() {
        $dish = Dish::find()->all();

        return [
            'data' => ArrayHelper::toArray($dish, [
                Dish::class => [
                    'id' => 'id',
                    'chefId' => 'chefId',
                    'name' => 'name',
                    'price' => 'price',
                ]
            ]),
        ];
    }
    
    public function actionChefDishes($chefId) {
        $dish = Dish::find()->where(['chefId' => $chefId])->all();
        if (empty($dish)) {
            Yii::$app->response->statusCode = 404;
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        return
                ArrayHelper::toArray($dish, [
                    Dish::class => [
                        'id' => 'id',
                        'chefId' => 'chefId',
                        'name' => 'name',
                        'price' => 'price',
                    ]
        ]);
    }
    
    public function actionIndex() {
        $searchModel = new DishSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dishes = $dataProvider->getModels();
        return
                ArrayHelper::toArray($dishes, [
                    Dish::class => [
                        'id' => 'id',
                        'chefId' => 'chefId',
                        'name' => 'name',
                        'price' => 'price',
                    ]
        ]);

        return $response;
    }

}