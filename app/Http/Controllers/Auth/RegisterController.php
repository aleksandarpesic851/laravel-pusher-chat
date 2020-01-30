<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\ChatkitController;
use Image;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048']
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $avatar = $this->save_avatar();
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'avatar' => $avatar
        ]);
        
        // Create Chat Room
        $chatkit = new ChatkitController();
        $chatkit->createRoom($user->id, $user->name, $avatar);

        // //Create Admin user in PUSHER chatkit
        // $chatkit->createUser("admin", "Administrator", "/avatar/admin.png");

        return $user;
    }

    private function save_avatar(){
        // Logic for user upload of avatar
        $filename = "default_user.png";
        if(request()->hasFile('avatar')){
            $avatar = request()->file('avatar');
            $filename = time() . "_" . $avatar->getClientOriginalName();
            Image::make($avatar)->resize(100, 100)->save( public_path('/avatar/' . $filename ) );
        }

        return '/avatar/' . $filename;
    }
}
