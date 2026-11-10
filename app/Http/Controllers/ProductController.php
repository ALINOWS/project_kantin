<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(): void {}

    public function store(Request $request): void {}

    public function show(Product $product): void {}

    public function update(Request $request, Product $product): void {}

    public function destroy(Product $product): void {}

    public function availability(Request $request, Product $product): void {}
}
