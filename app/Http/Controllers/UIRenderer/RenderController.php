<?php

namespace App\Http\Controllers\UIRenderer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RenderController extends Controller
{
    public function login(){
        return Inertia::render('auth/Login');
    }
    public function register(){
        return Inertia::render('auth/Register');
    }
}
