<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function index(){
        return view('auth.login');
    }

    public function login(Request $request){
        $credentials = $request->only('email', 'password');

        if (auth()->attempt($credentials)) {
            return redirect()->intended('/');
        }
        // dd('Invalid credentials');

        return redirect()->back()->with('error', 'Invalid credentials');
    }

    public function logout(){
        auth()->logout();
        return redirect()->route('login');
    }
}
