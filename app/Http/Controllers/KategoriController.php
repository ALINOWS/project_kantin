<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index(): void {}

    public function store(Request $request): void {}

    public function show(Kategori $kategori): void {}

    public function update(Request $request, Kategori $kategori): void {}

    public function destroy(Kategori $kategori): void {}
}
