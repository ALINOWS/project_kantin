<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(): string
    {
        return $this->pageText('Payments');
    }

    public function show(Payment $payment): string
    {
        return $this->pageText('Payments');
    }

    public function claimPaid(Request $request, Payment $payment): string
    {
        return $this->pageText('Payments');
    }

    public function verify(Request $request, Payment $payment): string
    {
        return $this->pageText('Payments');
    }
}
