### laravel-8 FB Graph API -> Исходный код Авторизация через FB и получения данных о пользователе, а также размещения постов на FB, через личный кабинет сайта...
_______________________________________________________________



### Facebook PHP SDK


#Давайте начнем с создания нового проекта Laravel, запустив

// https://github.com/laravel/socialite
// https://github.com/facebookarchive/php-graph-sdk

Добавьте laravel/socialite и facebook/graph-sdk в файл composer.json

```php
  "require": {
      [...] // other dependencies
      "facebook/graph-sdk": "^5.6",
      "laravel/socialite": "^3.0",
  },
```
и запустите ```composer install```, чтобы установить их. Мы используем пакет socialite для аутентификации пользователей с помощью сервиса Facebook OAuth. После аутентификации мы будем хранить токен доступа пользователя в базе данных и использовать его для совершения звонков в службу OAuth от имени пользователя. Я написал очень подробный учебник по социальной аутентификации с помощью socialite (https://quantizd.com/laravel-social-authentication-with-socialite/). Выполните следующие действия, чтобы интегрировать Facebook SDK в ваше приложение laravel и использовать его для публикации сообщений в профиле и на страницах.


# Шаг 1: Создайте приложение Facebook.
Посетите консоль разработчиков facebook и создайте новое приложение. Заполните данные и создайте идентификатор приложения. Мы будем использовать это приложение для аутентификации пользователей и публикации от их имени. Перейдите в Настройки > Основные и скопируйте идентификатор приложения и секрет. Добавьте новый продукт и выберите Facebook login.

# Шаг 2: Заполните учетные данные Facebook
Редактировать файл ```config/service.php```. Добавьте следующие строки в конце массива служб.

```php
'facebook' => [
    'client_id' =>> env('FACEBOOK_APP_ID'),         // Ваш идентификатор клиента приложения Facebook
    'client_secret' =>> env('FACEBOOK_APP_SECRET'), // Ваш секрет клиента приложения Facebook
    'redirect' =>> env('FACEBOOK_REDIRECT'), // Маршрут приложения, используемый для перенаправления пользователей обратно в приложение после аутентификации
    'default_graph_version' => > 'v2.12',
],
```

Отредактируйте файл ```.env``` вашего проекта.

```php
FACEBOOK_APP_ID=367634851613759 
FACEBOOK_APP_SECRET=b2385b3719b6aac14925fa116366c768 
FACEBOOK_REDIRECT=https://localhost:3000/login/facebook/callback
```

# Шаг 3: Добавьте URL-адрес перенаправления в приложение Valid OAuth Redirect URI
Перейдите в свое приложение > Продукты >> Вход в Facebook >>> Настройки и обновление >>> Действительные URI перенаправления OAuth. Для приложений, созданных после марта 2018 года, обязательным является использование HTTPS для использования сервиса OAuth. Даже если вы делаете запрос из своей локальной среды разработки, у вас должен быть включен https, чтобы использовать их сервис. Вы не можете отключить Принудительные параметры HTTPS в настройках вашего приложения.

# Шаг 4: Интеграция Facebook
Мы используем поставщиков услуг Laravel для начальной загрузки Facebook, чтобы избежать ненужного создания экземпляра объекта Facebook каждый раз, когда мы будем звонить в службу Facebook OAuth. Создайте новый FacebookServiceProvider.

```php artisan make:provider FacebookServiceProvider```

Теперь отредактируйте файл ```app/Providers/FacebookServiceProviders.php``` и добавьте следующий код.

```php
<?php
 
namespace App\Providers;
 
use Facebook\Facebook;
use Illuminate\Support\ServiceProvider;
 
class FacebookServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
 
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Facebook::class, function ($app) {
            $config = config('services.facebook');
            return new Facebook([
                'app_id' => $config['client_id'],
                'app_secret' => $config['client_secret'],
                'default_graph_version' => 'v2.6',
            ]);
        });
    }
}
```

В методе ```register()``` нашего поставщика услуг мы определяем реализацию Facebook\Facebook в сервисном контейнере. Мы используем встроенный в Laravel метод ```config()``` для получения учетных данных Facebook OAuth из файла ```config/services.php``` и используем его для построения объекта класса Facebook, который мы будем использовать во всем нашем приложении для вызова API Facebook.

# Шаг 5: Миграция базы Данных
После заполнения учетных данных базы данных в файле ```.env``` вашего проекта. Модифицируйте и запускайте миграции для создания таблиц базы данных, необходимых для хранения нашего пользователя OAuth. Вот как выглядит наша миграция пользователей.

```php
public function up()
{
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password')->nullable(); // Set to nullable
        $table->string('token'); // OAuth Token
        $table->rememberToken();
        $table->timestamps();
    });
}
```

#Шаг 6: Настройка маршрутов аутентификации и контроллеров
Запустите ```artisan make:auth``` , чтобы сгенерировать леса аутентификации Laravel по умолчанию. Отредактируйте файл ```routes/web.php```, чтобы добавить маршруты социальной аутентификации.

```php
Route::get('/login/facebook', 'Auth\LoginController@redirectToFacebookProvider');
Route::get('login/facebook/callback', 'Auth\LoginController@handleProviderFacebookCallback');
```

Edit ```app/Http/Controllers/Auth/LoginController.php```

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Socialize;


class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Redirect the user to the Facebook authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToFacebookProvider()
    {
		return Socialite::driver('facebook')->scopes(["", "public_profile"])->redirect();
    }
    /**
     * Obtain the user information from Facebook.
     *
     * @return void
     */
    public function handleProviderFacebookCallback()
    {
		
        $auth_user = Socialite::driver('facebook')->user();

        $user = User::updateOrCreate(
            [
                'email' => $auth_user->email
            ],
            [
                'token' => $auth_user->token,
                'name'  =>  $auth_user->name,				
            ]
        );

        Auth::login($user, true); 
        return redirect()->to('/user'); // Redirect to a secure page

    }
}
```


В методе ```redirectToFacebookProvider()``` мы запрашиваем разрешения на получение данных и управление профилями и страницами с помощью метода Socialite scopes. Facebook Facebook OAuth Пользователь приложения будет перенаправлен на страницу Facebook OAuth после посещения ```/login/facebook``` route. После принятия они будут перенаправлены обратно на маршрут ```login/facebook/callback```, и мы получим их токен доступа и данные профиля, чтобы создать нового пользователя или обновить существующий с новым токеном. Теперь мы будем использовать этот токен для выполнения вызовов Graph API от имени пользователя.


# Получение профиля пользователя с помощью Graph API
Давайте создадим новый контроллер для обработки запросов на использование Graph API.

Edit ```app/Http/Controllers/GraphController.php```

```php
<?php

namespace App\Http\Controllers;

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GraphController extends Controller
{

    private $api;
    public function __construct(Facebook $fb)
    {
        $this->middleware(function ($request, $next) use ($fb) {
            $fb->setDefaultAccessToken(Auth::user()->token);
            $this->api = $fb;
            return $next($request);
        });
    }

    public function retrieveUserProfile(){
        try {
            $params = "name,first_name,last_name,age_range,birthday,languages,gender,email,picture";
            $user = $this->api->get('/me?fields='.$params)->getGraphUser();
            dd($user);
        } catch (FacebookSDKException $e) {

        }

    }
/*
    public function publishToProfile(Request $request){
        try {
            $response = $this->api->post('/me/feed', [
                'message' => $request->message
            ]);
            dd($response);
        } catch (FacebookSDKException $e) {

        }
    }

    public function publishToPage(){

        $page_id = '107862144852697';

        try {
            $post = $this->api->post('/' . $page_id . '/feed', array('message' => 'New post...'), $this->getPageAccessToken($page_id));

            $post = $post->getGraphNode()->asArray();

            dd($post);

        } catch (FacebookSDKException $e) {
            dd($e); // handle exception
        }
    }

    public function getPageAccessToken($page_id){
        try {
            // Get the \Facebook\GraphNodes\GraphUser object for the current user.
            // If you provided a 'default_access_token', the '{access-token}' is optional.
            $response = $this->api->get('/me/accounts', Auth::user()->token);
        } catch(FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        try {
            $pages = $response->getGraphEdge()->asArray();
            foreach ($pages as $key) {
                if ($key['id'] == $page_id) {
                    return $key['access_token'];
                }
            }
        } catch (FacebookSDKException $e) {
            dd($e); // handle exception
        }
    }
*/
}
```
