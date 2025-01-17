<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Users\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravolt\Avatar\Avatar;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        if (! Auth::attempt($this->credentials($request))) {
            return $this->error('Credentials not match', 401);
        }

        $user = [
            'id' => auth()->user()->id,
            'name' => auth()->user()->name,
            'username' => auth()->user()->username,
            'email' => auth()->user()->email,
            'avatar' => asset(auth()->user()->avatar),
            'email_verified_at' => auth()->user()->email_verified_at,
            'created_at' => auth()->user()->created_at,
            'updated_at' => auth()->user()->updated_at,
        ];

        return $this->success([
            'user' => $user,
            'token' => auth()->user()->createToken('API Token')->plainTextToken,
        ]);
    }

    public function register(Request $request)
    {
        $attr = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $attr['name'],
            'username' => $attr['username'],
            'email' => $attr['email'],
            'password' => Hash::make($attr['password']),
        ]);

        $user->assignRole('normal-user');

        $path = 'uploads/avatars/'.uniqid().'-'.now()->timestamp.'.png';
        $avatar = new Avatar(config('laravolt.avatar'));
        $avatar->create($user->name)->save($path, 100);
        $user->avatar = $path;
        $user->save();

        $user->sendEmailVerificationNotification();

        return $this->success([
            'user' => $user,
        ], 'User created!');
    }

    public function logout(Request $request)
    {
        $attr = $request->validate([
            'id' => 'required|integer',
        ]);

        // Get user who requested the logout
        $user = User::findOrFail($attr['id']);

        // Revoke current user token
        $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();

        return [
            'message' => 'Tokens Revoked',
        ];
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        $login = request()->input('login');

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        request()->merge([$field => $login]);

        return $field;
    }
}
