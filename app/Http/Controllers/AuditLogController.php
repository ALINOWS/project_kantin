<?php

namespace App\Http\Controllers;

class AuditLogController extends Controller
{
    public function index(): string
    {
        return $this->pageText('Audit Logs');
    }
}
