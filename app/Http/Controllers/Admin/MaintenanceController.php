<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaintenanceResource;
use App\Models\Maintenance;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('admin/maintenance/index', [
            'maintenances' => MaintenanceResource::collection(Maintenance::with('organization')->latest()->get()),
        ]);
    }
}
