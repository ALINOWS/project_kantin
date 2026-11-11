<?php

namespace App\Http\Controllers;

use App\Models\MerchantProfile;
use Illuminate\Http\Request;

class MerchantProfileController extends Controller
{
    public function index(): string
    {
        return $this->pageText('Merchant Profiles');
    }

    public function store(Request $request): string
    {
        return $this->pageText('Merchant Profiles');
    }

    public function show(MerchantProfile $merchantProfile): string
    {
        return $this->pageText('Merchant Profiles');
    }

    public function update(Request $request, MerchantProfile $merchantProfile): string
    {
        return $this->pageText('Merchant Profiles');
    }

    public function destroy(MerchantProfile $merchantProfile): string
    {
        return $this->pageText('Merchant Profiles');
    }
}
