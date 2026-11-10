<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\Request;

class QrCodeController extends Controller
{
    public function index(): void {}

    public function store(Request $request): void {}

    public function show(QrCode $qrCode): void {}

    public function update(Request $request, QrCode $qrCode): void {}

    public function destroy(QrCode $qrCode): void {}
}
