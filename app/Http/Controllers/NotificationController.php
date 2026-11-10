<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): void {}

    public function markRead(Request $request, Notification $notification): void {}
}
