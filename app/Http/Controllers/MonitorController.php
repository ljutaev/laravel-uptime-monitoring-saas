<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Monitor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

class MonitorController extends Controller
{

    use AuthorizesRequests, ValidatesRequests;
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
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'check_interval' => 'required|integer|min:1|max:60',
            'notifications_enabled' => 'boolean',
        ]);

        $user->monitors()->create($validated);

        return redirect()->route('monitors.index')
            ->with('success', 'Successfully created monitor.');
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
    public function edit(Monitor $monitor)
    {
        $this->authorize('update', $monitor);

        return Inertia::render('User/Monitors/Edit', [
            'monitor' => [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'url' => $monitor->url,
                'type' => $monitor->type,
                'check_interval' => $monitor->check_interval,
                'notifications_enabled' => $monitor->notifications_enabled,
            ],
            'minInterval' => 1,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Monitor $monitor)
    {
        $this->authorize('update', $monitor);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'check_interval' => 'required|integer|min:1|max:60',
            'notifications_enabled' => 'boolean',
        ]);

        $monitor->update($validated);

        return redirect()->route('monitors.index')
            ->with('success', 'Successfully updated monitor.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Monitor $monitor)
    {
        $this->authorize('delete', $monitor);

        $monitor->delete();

        return back()->with('success', 'Deleted monitor successfully.');
    }
}
