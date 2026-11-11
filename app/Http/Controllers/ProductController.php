<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(): string
    {
        return $this->pageText('Products');
    }

    public function store(Request $request): string
    {
        return $this->pageText('Products');
    }

    public function show(Product $product): string
    {
        return $this->pageText('Products');
    }

    public function update(Request $request, Product $product): string
    {
        return $this->pageText('Products');
    }

    public function destroy(Product $product): string
    {
        return $this->pageText('Products');
    }

    public function availability(Request $request, Product $product): string
    {
        return $this->pageText('Products');
    }
}
