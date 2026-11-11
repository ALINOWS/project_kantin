<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): string
    {
        return $this->pageText('Orders');
    }

    public function store(Request $request): string
    {
        return $this->pageText('Orders');
    }

    public function show(Order $order): string
    {
        return $this->pageText('Orders');
    }

    public function updateStatus(Request $request, Order $order): string
    {
        return $this->pageText('Orders');
    }

    public function claimPickup(Request $request, Order $order): string
    {
        return $this->pageText('Orders');
    }
}
