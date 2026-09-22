<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = \App\Models\Event::whereHas('sales', function ($q) {
                $q->where('users.id', auth()->id());
            })
            ->orderBy('tanggal_mulai', 'asc')
            ->get();
            
        return view('sales.event.index', compact('events'));
    }
}
