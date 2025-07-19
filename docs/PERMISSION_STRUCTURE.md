# Permission Structure Documentation

## Overview

The application uses a hierarchical permission system with two levels:
1. **Organization-level permissions** (stored in `organization_user` table)
2. **Team-level permissions** (stored in `team_user` table)

## Permission Hierarchy

### Organization Roles (organization_user table)

| Role | Description | Default Permissions |
|------|-------------|-------------------|
| `owner` | Full organization control | All permissions enabled |
| `admin` | Full organization control | All permissions enabled |
| `team_lead` | Can manage teams and resources | `manage_teams`, `manage_services`, `manage_incidents`, `manage_maintenance`, `view_analytics` |
| `member` | Basic access | No management permissions |

### Team Roles (team_user table)

| Role | Description | Default Permissions |
|------|-------------|-------------------|
| `lead` | Team management | `manage_teams`, `manage_services`, `manage_incidents`, `manage_maintenance`, `view_analytics` |
| `member` | Team member | `manage_incidents`, `manage_maintenance` |

## Available Permissions

| Permission | Description | Scope |
|------------|-------------|-------|
| `manage_organization` | Update organization settings, branding, configuration | Organization |
| `manage_users` | Invite, remove, manage user roles and permissions | Organization |
| `manage_teams` | Create, edit, delete teams and assign team members | Both |
| `manage_services` | Create, edit, delete services and update their status | Both |
| `manage_incidents` | Create, update, and resolve incidents | Both |
| `manage_maintenance` | Schedule and manage maintenance windows | Both |
| `view_analytics` | Access analytics and reporting features | Both |

## Permission Resolution Logic

1. **System Admin**: Has access to everything
2. **Organization Permission**: If user has organization-level permission, they can access all resources
3. **Team Permission**: If user has team-level permission, they can access team-specific resources
4. **Final Permission**: Organization permission OR Team permission (whichever grants access)

## Database Structure

### organization_user table
```sql
- organization_id (FK)
- user_id (FK)
- role (enum: 'owner', 'admin', 'team_lead', 'member')
- permissions (JSON) - Granular permissions
- is_active (boolean)
- invited_by (FK to users)
- joined_at (timestamp)
```

### team_user table
```sql
- team_id (FK)
- user_id (FK)
- role (enum: 'lead', 'member')
- permissions (JSON) - Granular permissions
```

## Key Methods

### PermissionService
- `assignDefaultOrganizationPermissions()` - Assigns default permissions based on organization role
- `assignDefaultTeamPermissions()` - Assigns default permissions based on team role
- `userHasOrganizationPermission()` - Checks organization-level permission
- `userHasTeamPermission()` - Checks team-level permission
- `userCan()` - Checks if user can perform action (considers both levels)

### Controllers
- **TeamController**: Manages team membership and role updates
- **OrganizationController**: Manages organization membership and role updates
- **InvitationController**: Handles invitation acceptance with default permission assignment

## Frontend Integration

### usePermissions Hook
The `use-permissions.tsx` hook provides:
- `hasOrganizationPermission()` - Check organization permissions
- `hasTeamPermission()` - Check team permissions
- `canManageService()` - Check service management permissions
- `canManageIncident()` - Check incident management permissions
- `canManageMaintenance()` - Check maintenance management permissions

## Default Permission Assignment

### When Users Join Organizations
1. User accepts invitation
2. `InvitationController::accept()` calls `assignDefaultOrganizationPermissions()`
3. Default permissions are assigned based on the invited role

### When Users Join Teams
1. Team lead adds member via `TeamController::addMember()`
2. `assignDefaultTeamPermissions()` is called
3. Default permissions are assigned based on the team role

### When Roles Are Updated
1. Organization role update: `OrganizationController::updateMemberRole()`
2. Team role update: `TeamController::updateMemberRole()`
3. Both call respective permission assignment methods

## Testing

The permission system is tested in `tests/Feature/PermissionTest.php`:
- Organization role permission assignment
- Team role permission assignment
- Permission hierarchy validation

## Migration Notes

- Organization permissions are stored in the `organization_user` pivot table
- Team permissions are stored in the `team_user` pivot table
- Both use JSON columns for granular permission storage
- Default permissions are defined as constants in `PermissionService`
- Custom permissions can override defaults when needed 