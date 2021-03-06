<?php
namespace frontend\controllers;

use Yii;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;
use yii\base\InvalidParamException;
use frontend\models\UserProfile;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use frontend\models\Auth;
use yii\authclient\ClientInterface;
use yii\helpers\ArrayHelper;
use common\models\User;
use common\components\SocialHelpers;
/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup','error','authfailure'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout','error','authfailure'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
            'auth' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'onAuthSuccess'],
            ],
        ];
    }

    public function actionIndex()
    {
      if (Yii::$app->user->isGuest) {
          return $this->render('index');
      } else {
        // user is logged in
        $this->redirect('meeting/index');
      }


    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending email.');
            }

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionPrivacy()
    {
        return $this->render('privacy');
    }

    public function actionTos()
    {
        return $this->render('tos');
    }

    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    return $this->goHome();
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->getSession()->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            } else {
                Yii::$app->getSession()->setFlash('error', 'Sorry, we are unable to reset password for email provided.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->getSession()->setFlash('success', 'New password was saved.');
            return $this->redirect('login');
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    public function actionAuthfailure()
    {
        return $this->render('authfailure');
    }

    public function actionUnavailable()
    {
        return $this->render('unavailable');
    }

    public function actionError()
    {
        return $this->render('error');
    }

    public function onAuthSuccess($client)
        {
          $mode =  Yii::$app->getRequest()->getQueryParam('mode');
          $attributes = $client->getUserAttributes();
          $serviceId = $attributes['id'];
          $serviceProvider = $client->getId();
          $serviceTitle = $client->getTitle();
          $firstname ='';
          $lastname='';
          $fullname ='';
          switch ($serviceProvider) {
            case 'facebook':
              $username = $email = $attributes['email'];
              $fullname = $attributes['name'];
              break;
            case 'google':
              $email = $attributes['emails'][0]['value'];
              if (isset($attributes['displayName'])) {
                  $fullname = $username = $attributes['displayName'];
              }
              if (isset($attributes['name']['familyName']) and isset($attributes['name']['givenName'])) {
                $lastname = $attributes['name']['familyName'];
                $firstname = $attributes['name']['givenName'];
              }
            break;
            case 'linkedin':
              $username = $email = $attributes['email-address'];
              $lastname = $attributes['first-name'];
              $firstname = $attributes['last-name'];
              $fullname = $firstname.' '.$lastname;
            break;
            case 'twitter':
              $username = $attributes['screen_name'];
              $fullname = $attributes['name'];
              // to do - fix social helpers
              $email = $serviceId.'@twitter.com';
            break;
          }
          // to do - split names into first and last with parser
            $auth = Auth::find()->where([
                'source' => (string)$serviceProvider,
                'source_id' => (string)$serviceId,
            ])->one();
            if (Yii::$app->user->isGuest) {
                if ($auth) {
                  // if the user_id associated with this oauth login is registered, try to log them in
                  $user_id = $auth->user_id;
                  $person = new \common\models\User;
                  $identity = $person->findIdentity($user_id);
                  User::completeInitialize($user_id);
                  UserProfile::applySocialNames($user_id,$firstname,$lastname,$fullname);
                  Yii::$app->user->login($identity);
                } else {
                  // it's a new oauth id
                  // first check if we know the email address
                  if (isset($email) && User::find()->where(['email' => $email])->exists()) {
                    // the email is already registered, ask person to link accounts after loggin in
                    Yii::$app->getSession()->setFlash('error', [
                        Yii::t('frontend', "The email in this {client} account is already registered. Please login using your username and password first, then link to this account in your profile settings.", ['client' => $serviceTitle]),
                    ]);
                    $this->redirect(['login']);
                  } else {
                    switch ($mode) {
                      case 'login':
                        // they were trying to login with an unconnected account - ask them to login normally and link after
                        Yii::$app->getSession()->setFlash('error', [
                            Yii::t('frontend', "We don't recognize the user with this email from {client}. If you wish to sign up, try again below. If you wish to link {client} to your Meeting Planner account, login first with your username and password. Then visit your profile settings.", ['client' => $serviceTitle]),
                        ]);
                        $this->redirect(['signup']);
                        break;
                      case 'signup':
                        // sign up a new account using oauth
                        // look for username that exists already and differentiate it
                        if (isset($username) && User::find()->where(['username' => $username])->exists()) {
                          $username.=Yii::$app->security->generateRandomString(6);
                        }
                        $password = Yii::$app->security->generateRandomString(12);
                          $user = new User([
                              'username' => $username, // $attributes['login'],
                              'email' => $email,
                              'password' => $password,
                              'status' => User::STATUS_ACTIVE,
                          ]);
                          $user->generateAuthKey();
                          $user->generatePasswordResetToken();
                          $transaction = $user->getDb()->beginTransaction();
                          if ($user->save()) {
                              $auth = new Auth([
                                  'user_id' => $user->id,
                                  'source' => $serviceProvider, // $client->getId(),
                                  'source_id' => $serviceId, // (string)$attributes['id'],
                              ]);
                              if ($auth->save()) {
                                  $transaction->commit();
                                  User::completeInitialize($user->id);
                                  UserProfile::applySocialNames($user->id,$firstname,$lastname,$fullname);
                                  Yii::$app->user->login($user);
                              } else {
                                  print_r($auth->getErrors());
                              }
                          } else {
                              print_r($user->getErrors());
                          }
                      break;
                      case 'schedule':
                      // to do - neeeds integration above as well
                      break;
                    }
                  }
                }
            } else {
              UserProfile::applySocialNames(Yii::$app->user->id,$firstname,$lastname,$fullname);
              // user already logged in, link the accounts
                if (!$auth) { // add auth provider
                    $auth = new Auth([
                        'user_id' => Yii::$app->user->id,
                        'source' => $serviceProvider,
                        'source_id' => $serviceId,
                    ]);
                    $auth->validate();
                    $auth->save();
                    $u = User::findOne(Yii::$app->user->id);
                    $u->status = User::STATUS_ACTIVE;
                    $u->update();
                    Yii::$app->session->setFlash('success', Yii::t('frontend', 'Your {serviceProvider} account has been connected to your Meeting Planner account. In the future you can log in with a single click of its logo.',
    array('serviceProvider'=>$serviceTitle)));
                }
            }
        }
}
