# Custom Permission Management System

## Overview

The application now supports custom permissions that can override default role permissions at both organization and team levels. This allows for fine-grained control over user access and capabilities.

## Key Features

### 1. **Custom Permission Override**
- Default role permissions can be customized per organization/team
- Custom permissions are stored in the database and take precedence over defaults
- Changes apply to all users with the specified role in that organization/team

### 2. **Permission Hierarchy**
- **Organization Level**: Custom permissions override organization role defaults
- **Team Level**: Custom permissions override team role defaults
- **Final Permission**: Organization permission OR Team permission (whichever grants access)

### 3. **Role-Based Management**
- **Organization Owners**: Can manage all organization role permissions
- **Team Leads**: Can manage team role permissions for their teams
- **Admins**: Can manage team members but not role permissions

## Database Structure

### organization_user table
```sql
- permissions (JSON) - Custom permissions for the user's organization role
```

### team_user table
```sql
- permissions (JSON) - Custom permissions for the user's team role
```

## Backend Implementation

### PermissionService Methods

#### Custom Permission Management
```php
// Update organization role permissions for all users with that role
updateOrganizationRolePermissions(Organization $organization, string $role, array $permissions)

// Update team role permissions for all users with that role
updateTeamRolePermissions(Team $team, string $role, array $permissions)

// Get custom permissions for organization role
getCustomOrganizationRolePermissions(Organization $organization, string $role)

// Get custom permissions for team role
getCustomTeamRolePermissions(Team $team, string $role)

// Get organization role permissions with custom support
getOrganizationRolePermissionsWithCustom(Organization $organization)

// Get team role permissions with custom support
getTeamRolePermissions(Team $team)
```

#### Permission Resolution
```php
// Check if user has organization permission (includes custom permissions)
userHasOrganizationPermission(User $user, Organization $organization, string $permission)

// Check if user has team permission (includes custom permissions)
userHasTeamPermission(User $user, Team $team, string $permission)

// Get user's permissions in organization (custom or default)
getUserOrganizationPermissions(User $user, Organization $organization)

// Get user's permissions in team (custom or default)
getUserTeamPermissions(User $user, Team $team)
```

### Controller Updates

#### OrganizationController
- `updateRolePermissions()` - Updates organization role permissions
- Only organization owners can update role permissions
- Updates all users with the specified role

#### TeamController
- `updateRolePermissions()` - Updates team role permissions
- Team leads can update permissions for their teams
- Updates all users with the specified role in the team

## Frontend Implementation

### RolePermissionManager Component

The `role-permission-manager.tsx` component provides a UI for managing custom permissions:

#### Features
- **Permission Toggle**: Checkboxes to enable/disable permissions
- **Role Display**: Shows current permissions for each role
- **User Count**: Displays number of users with each role
- **Real-time Updates**: Changes are applied immediately
- **Scope Support**: Handles both organization and team permissions

#### Usage
```tsx
<RolePermissionManager
    type="organization" // or "team"
    entityId={organization.id} // or team.id
    roles={rolePermissions}
    onUpdatePermissions={async (role, permissions) => {
        // Handle permission updates
    }}
/>
```

### Permission Scopes

#### Organization Permissions
- `manage_organization` - Organization settings and branding
- `manage_users` - User management and invitations
- `manage_teams` - Team creation and management
- `manage_services` - Service management
- `manage_incidents` - Incident management
- `manage_maintenance` - Maintenance management
- `view_analytics` - Analytics access

#### Team Permissions
- `manage_teams` - Team member management
- `manage_services` - Service management within team
- `manage_incidents` - Incident management within team
- `manage_maintenance` - Maintenance management within team
- `view_analytics` - Analytics access

## API Endpoints

### Get Available Permissions
```
GET /teams/available-permissions?scope=organization
GET /teams/available-permissions?scope=team
```

### Update Organization Role Permissions
```
PATCH /settings/organization/permissions
{
    "role": "member",
    "permissions": {
        "manage_services": true,
        "manage_incidents": true,
        "view_analytics": true
    }
}
```

### Update Team Role Permissions
```
PATCH /teams/{team}/permissions
{
    "role": "member",
    "permissions": {
        "manage_services": true,
        "manage_incidents": true,
        "manage_maintenance": true
    }
}
```

## Usage Examples

### 1. Customizing Organization Member Permissions

**Scenario**: Allow organization members to manage services and incidents

```php
$permissionService = app(PermissionService::class);

$customPermissions = [
    'manage_organization' => false,
    'manage_users' => false,
    'manage_teams' => false,
    'manage_services' => true,  // Override: members can manage services
    'manage_incidents' => true, // Override: members can manage incidents
    'manage_maintenance' => false,
    'view_analytics' => false,
];

$permissionService->updateOrganizationRolePermissions($organization, 'member', $customPermissions);
```

### 2. Customizing Team Member Permissions

**Scenario**: Allow team members to manage services and view analytics

```php
$customPermissions = [
    'manage_teams' => false,
    'manage_services' => true,  // Override: members can manage services
    'manage_incidents' => true,
    'manage_maintenance' => true,
    'view_analytics' => true,   // Override: members can view analytics
];

$permissionService->updateTeamRolePermissions($team, 'member', $customPermissions);
```

### 3. Checking Custom Permissions

```php
// Check if user has custom permission
$canManageServices = $permissionService->userHasOrganizationPermission($user, $organization, 'manage_services');

// Get all user permissions (custom or default)
$permissions = $permissionService->getUserOrganizationPermissions($user, $organization);
```

## Frontend Integration

### Organization Team Management
- Located at: `resources/js/pages/settings/organization-team.tsx`
- Only organization owners can access permission management
- Shows all organization roles with current permissions

### Team Management
- Located at: `resources/js/pages/teams/show.tsx`
- Team leads can manage permissions for their teams
- Shows team roles with current permissions

### Permission Hook
The `use-permissions.tsx` hook automatically handles custom permissions:
- `hasOrganizationPermission()` - Checks organization permissions (custom or default)
- `hasTeamPermission()` - Checks team permissions (custom or default)
- `canManageService()` - Checks service management permissions
- `canManageIncident()` - Checks incident management permissions
- `canManageMaintenance()` - Checks maintenance management permissions

## Testing

### Test Coverage
- `tests/Feature/CustomPermissionTest.php` - Tests custom permission functionality
- `tests/Feature/PermissionTest.php` - Tests basic permission system

### Test Scenarios
1. **Custom Organization Permissions**: Verify custom permissions override defaults
2. **Custom Team Permissions**: Verify custom permissions override defaults
3. **Permission Data Retrieval**: Verify custom permissions are returned correctly
4. **Permission Hierarchy**: Verify organization and team permissions work together

## Migration Notes

### Existing Users
- Users with existing roles will automatically get default permissions
- Custom permissions can be applied without affecting existing functionality
- Backward compatibility is maintained

### Database Changes
- No new migrations required
- Uses existing `permissions` JSON columns
- Cache is used for performance optimization

## Best Practices

### 1. Permission Design
- Start with default permissions and customize as needed
- Keep permissions granular for better control
- Document custom permission changes

### 2. Security
- Only organization owners can modify organization permissions
- Team leads can only modify their team's permissions
- Validate permission changes on both frontend and backend

### 3. Performance
- Custom permissions are cached for performance
- Cache is cleared when permissions are updated
- Use bulk updates for multiple users

### 4. User Experience
- Provide clear feedback when permissions are updated
- Show current permissions in the UI
- Allow easy reversion to defaults

## Troubleshooting

### Common Issues

1. **Permissions Not Updating**
   - Check if user has permission to modify role permissions
   - Verify cache is cleared after updates
   - Check database for custom permission storage

2. **Custom Permissions Not Applied**
   - Verify custom permissions are stored in the database
   - Check permission resolution logic
   - Ensure proper role assignment

3. **Frontend Not Reflecting Changes**
   - Clear browser cache
   - Check API responses
   - Verify permission data structure

### Debug Tools
- Check `organization_user.permissions` and `team_user.permissions` columns
- Use `getCustomOrganizationRolePermissions()` and `getCustomTeamRolePermissions()`
- Monitor cache keys for permission data 