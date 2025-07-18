<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * Show the invitation acceptance page.
     */
    public function show(string $token): Response
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation) {
            abort(404, 'Invitation not found.');
        }

        if ($invitation->isExpired()) {
            abort(410, 'This invitation has expired.');
        }

        if ($invitation->isAccepted()) {
            abort(410, 'This invitation has already been accepted.');
        }

        return Inertia::render('auth/accept-invitation', [
            'invitation' => [
                'id' => $invitation->id,
                'token' => $invitation->token,
                'email' => $invitation->email,
                'name' => $invitation->name,
                'role' => $invitation->role,
                'message' => $invitation->message,
                'organization' => [
                    'id' => $invitation->organization->id,
                    'name' => $invitation->organization->name,
                ],
                'invited_by' => [
                    'name' => $invitation->invitedBy->name,
                ],
                'expires_at' => $invitation->expires_at,
            ],
        ]);
    }

    /**
     * Accept the invitation and create/update user account.
     */
    public function accept(Request $request, string $token)
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation) {
            return back()->withErrors(['token' => 'Invalid invitation token.']);
        }

        if ($invitation->isExpired()) {
            return back()->withErrors(['token' => 'This invitation has expired.']);
        }

        if ($invitation->isAccepted()) {
            return back()->withErrors(['token' => 'This invitation has already been accepted.']);
        }

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Check if user already exists
        $user = User::where('email', $invitation->email)->first();

        if ($user) {
            // Update existing user's password
            $user->update([
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);
        } else {
            // Create new user
            $user = User::create([
                'name' => $invitation->name,
                'email' => $invitation->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
                'organization_id' => $invitation->organization_id, // Legacy field
                'role' => $invitation->role, // Legacy field
            ]);
        }

        // Add user to organization through pivot table
        $invitation->organization->addUser($user, $invitation->role, $invitation->invitedBy);

        // Mark invitation as accepted
        $invitation->update([
            'accepted_at' => now(),
        ]);

        // Log the user in
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Welcome to ' . $invitation->organization->name . '!');
    }
}
