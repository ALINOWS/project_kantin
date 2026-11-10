<?php

namespace App\Http\Controllers;

use App\Models\MerchantProfile;
use Illuminate\Http\Request;

class MerchantProfileController extends Controller
{
    public function index(): void {}

    public function store(Request $request): void {}

    public function show(MerchantProfile $merchantProfile): void {}

    public function update(Request $request, MerchantProfile $merchantProfile): void {}

    public function destroy(MerchantProfile $merchantProfile): void {}
}
