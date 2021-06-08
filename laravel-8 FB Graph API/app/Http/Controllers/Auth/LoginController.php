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
		
		//$driver = Socialite::driver('facebook')
			//->fields(['id', 'name', 'first_name', 'email', 'languages']);
		
		return Socialite::driver('facebook')->scopes(["", "public_profile"])->redirect();
		//$request->session()->save();
	
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
