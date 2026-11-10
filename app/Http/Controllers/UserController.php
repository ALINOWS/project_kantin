<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(): void {}

    public function store(Request $request): void {}

    public function show(User $user): void {}

    public function update(Request $request, User $user): void {}

    public function destroy(User $user): void {}
}
