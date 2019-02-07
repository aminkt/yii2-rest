<?php


namespace aminkt\yii2\rest\controllers;

use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\filters\Cors;
use yii\web\Response;

/**
 * Trait RestControllerTrait
 * Use this trait in your rest controller to handle auth and CORS.
 *
 * @property array $optionalAuthRoutes  Add action ids that do not need auth.
 *                                      Auth will available but not required in this action.
 * @property array $onlyAuthRoutes      Add action ids that need auth.
 * @package rest\components
 *
 * @author  Amin Keshavarz <ak_1596@yahoo.com>
 */
trait RestControllerTrait
{
    /**
     * @var bool See details {@link \yii\web\Controller::$enableCsrfValidation}.
     */
    public $enableCsrfValidation = false;

    /**
     * Create an error for api response.
     *
     * @param array|string $message
     * @param int          $code
     *
     * @return array
     */
    public static function error($message, $code = 400)
    {
        \Yii::$app->response->setStatusCode($code);
        return $message;
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors = $this->addCorsBehavioes($behaviors);

        // re-add authentication filter
        $behaviors['authenticator']['optional'] = $this->getOptionalAuthRoutes();
        $behaviors['authenticator']['only'] = $this->getOnlyAuthRoutes();

        return $behaviors;
    }

    /**
     * Return list of action ids that auth not required but allowed.
     *
     * @return array
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function getOptionalAuthRoutes(): array
    {
        if (property_exists($this, 'optionalAuthRoutes')) {
            return $this->optionalAuthRoutes;
        }

        return ['*'];
    }

    /**
     * Return list of action ids that auth required.
     *
     * @return array
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function getOnlyAuthRoutes(): array
    {
        if (property_exists($this, 'onlyAuthRoutes')) {
            return $this->onlyAuthRoutes;
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    protected function addCorsBehavioes($behaviors)
    {

        unset($behaviors['authenticator']);

        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                // restrict access to
                'Origin' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Request-Headers' => ['content-type', 'authorization', 'accept'],
                'Access-Control-Expose-Headers' => [
                    'x-pagination-current-page',
                    'x-pagination-page-count',
                    'x-pagination-per-page',
                    'x-pagination-total-count'
                ]
            ],
        ];


        $behaviors = $this->addContentNegotiatorBehavior($behaviors);

        $behaviors = $this->addAuthBehavior($behaviors);

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    protected function addContentNegotiatorBehavior($behaviors)
    {
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON
            ],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    protected function addAuthBehavior($behaviors)
    {
        unset($behaviors['authenticator']);

        // re-add authentication filter
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['options'],
            'optional' => ['*']
        ];

        return $behaviors;
    }

    /**
     * Handle options request.
     *
     * @param $action
     *
     * @return bool
     *
     * @throws \yii\base\ExitException
     * @throws \yii\web\BadRequestHttpException
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function beforeAction($action)
    {
        if (\Yii::$app->getRequest()->isOptions) {
            $cors = $this->prepareCors();
            \Yii::$app->getResponse()->getHeaders()->set("Access-Control-Allow-Origin", $cors['origin']);
            \Yii::$app->getResponse()->getHeaders()->set("Access-Control-Allow-Methods", $cors['method']);
            \Yii::$app->getResponse()->getHeaders()->set("Access-Control-Allow-Headers", $cors['headers']);
            \Yii::$app->getResponse()->getHeaders()->set("Access-Control-Allow-Credentials", "true");
            \Yii::$app->getResponse()->getHeaders()->set("Allow", $cors['method']);
            \Yii::$app->end();
        }

        return parent::beforeAction($action);
    }

    /**
     * Prepare cors data from request header.
     *
     * @return array
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    private function prepareCors()
    {
        $origin = \Yii::$app->getRequest()->getHeaders()->get('origin');
        $method = \Yii::$app->getRequest()->getHeaders()->get("Access-Control-Request-Method");
        $headers = \Yii::$app->getRequest()->getHeaders()->get("Access-Control-Request-Headers");

        return [
            'origin' => $origin,
            'method' => $method,
            "headers" => $headers
        ];
    }
}