<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request): string
    {
        return $this->pageText('Cart');
    }

    public function addItem(Request $request): string
    {
        return $this->pageText('Cart');
    }

    public function updateItem(Request $request, CartItem $cartItem): string
    {
        return $this->pageText('Cart');
    }

    public function removeItem(Request $request, CartItem $cartItem): string
    {
        return $this->pageText('Cart');
    }
}
