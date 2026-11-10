<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request): void {}

    public function addItem(Request $request): void {}

    public function updateItem(Request $request, CartItem $cartItem): void {}

    public function removeItem(Request $request, CartItem $cartItem): void {}
}
