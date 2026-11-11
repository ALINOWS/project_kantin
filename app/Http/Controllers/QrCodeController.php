<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\Request;

class QrCodeController extends Controller
{
    public function index(): string
    {
        return $this->pageText('QR Codes');
    }

    public function store(Request $request): string
    {
        return $this->pageText('QR Codes');
    }

    public function show(QrCode $qrCode): string
    {
        return $this->pageText('QR Codes');
    }

    public function update(Request $request, QrCode $qrCode): string
    {
        return $this->pageText('QR Codes');
    }

    public function destroy(QrCode $qrCode): string
    {
        return $this->pageText('QR Codes');
    }
}
