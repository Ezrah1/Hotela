<?php

namespace App\Modules\Website\Controllers;

use App\Core\Controller;
use App\Core\Request;

class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('website/home', [
            'pageTitle' => 'Hotela | Hotel Management Platform',
        ]);
    }
}


