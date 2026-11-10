<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): void {}

    public function store(Request $request): void {}

    public function show(Order $order): void {}

    public function updateStatus(Request $request, Order $order): void {}

    public function claimPickup(Request $request, Order $order): void {}
}
