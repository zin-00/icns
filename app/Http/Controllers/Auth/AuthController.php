<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function login(Request $request){
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);
        $user = User::findBy('email', $credentials['email']);
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return Inertia::render('auth/Login', ['error' => 'The provided credentials are incorrect.']);
        }
        // Authentication passed...
        return Inertia::render('Dashboard');
    }
    public function logout(Request $request){

    }
    public function register(Request $request){
        $credentials = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        // Create the user
        $user = User::create([
            'name' => $credentials['name'],
            'email' => $credentials['email'],
            'password' => Hash::make($credentials['password']),
        ]);

        return Inertia::render('auth/Login');
    }

}
