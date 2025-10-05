<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class MonitorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $monitors = auth()->user()->monitors()
            ->latest()
            ->get()
            ->map(fn($monitor) => [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'url' => $monitor->url,
                'type' => $monitor->type,
                'status' => $monitor->status,
                'status_color' => $monitor->status_color,
                'check_interval' => $monitor->check_interval,
                'uptime_30d' => $monitor->uptime_30d,
                'last_checked_at' => $monitor->last_checked_at?->diffForHumans(),
                'created_at' => $monitor->created_at->format('d M, Y'),
            ]);

        return Inertia::render('User/Monitors/Index',[
            'monitors' => $monitors,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('User/Monitors/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
