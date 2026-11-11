<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(): string
    {
        return $this->pageText('Users');
    }

    public function store(Request $request): string
    {
        return $this->pageText('Users');
    }

    public function show(User $user): string
    {
        return $this->pageText('Users');
    }

    public function update(Request $request, User $user): string
    {
        return $this->pageText('Users');
    }

    public function destroy(User $user): string
    {
        return $this->pageText('Users');
    }
}
