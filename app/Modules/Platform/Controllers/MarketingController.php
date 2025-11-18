<?php

namespace App\Modules\Platform\Controllers;

use App\Core\Controller;

class MarketingController extends Controller
{
    public function home(): void
    {
        $this->view('platform/home', [
            'pageTitle' => 'Hotela OS | Hospitality Platform',
        ]);
    }
}

