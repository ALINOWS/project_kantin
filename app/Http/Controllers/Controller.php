<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    protected function pageText(string $name): string
    {
        return "ini halaman {$name}";
    }
}
