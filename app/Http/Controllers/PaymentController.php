<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(): void {}

    public function show(Payment $payment): void {}

    public function claimPaid(Request $request, Payment $payment): void {}

    public function verify(Request $request, Payment $payment): void {}
}
