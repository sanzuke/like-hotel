<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Apartment;

class HomeController extends Controller
{
    public function index()
    {
        return view('site.home', [
            'apartments' => Apartment::active()->latest('id')->limit(6)->get(),
        ]);
    }
}
