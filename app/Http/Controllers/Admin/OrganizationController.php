<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('admin/organizations/index', [
            'organizations' => OrganizationResource::collection(Organization::with('users')->get()),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Organization $organization): Response
    {
        return Inertia::render('admin/organizations/show', [
            'organization' => new OrganizationResource($organization->load('users')),
        ]);
    }
}
