<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(): string
    {
        return $this->pageText('Reviews');
    }

    public function store(Request $request): string
    {
        return $this->pageText('Reviews');
    }

    public function destroy(Review $review): string
    {
        return $this->pageText('Reviews');
    }
}
