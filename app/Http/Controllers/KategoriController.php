<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index(): string
    {
        return $this->pageText('Kategori');
    }

    public function store(Request $request): string
    {
        return $this->pageText('Kategori');
    }

    public function show(Kategori $kategori): string
    {
        return $this->pageText('Kategori');
    }

    public function update(Request $request, Kategori $kategori): string
    {
        return $this->pageText('Kategori');
    }

    public function destroy(Kategori $kategori): string
    {
        return $this->pageText('Kategori');
    }
}
