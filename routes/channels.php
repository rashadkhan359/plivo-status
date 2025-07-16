<?php

use Illuminate\Support\Facades\Broadcast;

// Private channel for organization (dashboard, management)
Broadcast::channel('organization.{orgId}', function ($user, $orgId) {
    return $user->organization_id == $orgId;
});

// Public channel for status page (no auth required)
Broadcast::channel('status.{orgSlug}', function () {
    return true;
}); 