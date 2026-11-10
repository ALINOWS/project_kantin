<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(): void {}

    public function store(Request $request): void {}

    public function destroy(Review $review): void {}
}
