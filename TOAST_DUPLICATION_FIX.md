# Toast Duplication Fix

## Problem
When attempting to delete roles in the ThinkTest AI admin panel, duplicate error toast notifications were appearing with the message "Cannot delete role that is assigned to users".

## Root Cause
The issue was caused by **duplicate `useToast()` hook calls**:

1. **Layout Level**: `app-sidebar-layout.tsx` calls `useToast()` to automatically handle Laravel flash messages
2. **Page Level**: Admin Index pages (`Roles/Index.tsx`, `Users/Index.tsx`, `Permissions/Index.tsx`) were also calling `useToast()` but only using it for loading toasts

Both hooks were listening for the same flash messages from Laravel, causing duplicate toast notifications when the controller returned error messages.

## Solution
Removed the redundant `useToast()` calls from the Admin Index pages and replaced them with direct `react-hot-toast` imports for loading toasts only.

### Files Modified

#### 1. `resources/js/pages/Admin/Roles/Index.tsx`
- **Before**: Imported and used `useToast()` hook
- **After**: Imported `toast` directly from `react-hot-toast`
- **Change**: Removed `const toast = useToast();` and replaced with direct toast usage

#### 2. `resources/js/pages/Admin/Users/Index.tsx`
- **Before**: Imported and used `useToast()` hook
- **After**: Imported `toast` directly from `react-hot-toast`
- **Change**: Removed `const toast = useToast();` and replaced with direct toast usage

#### 3. `resources/js/pages/Admin/Permissions/Index.tsx`
- **Before**: Imported and used `useToast()` hook
- **After**: Imported `toast` directly from `react-hot-toast`
- **Change**: Removed `const toast = useToast();` and replaced with direct toast usage

## Architecture
The toast notification system now follows this pattern:

- **Layout Level**: `app-sidebar-layout.tsx` handles all automatic Laravel flash messages
- **Page Level**: Pages only use direct `toast` calls for manual notifications (loading, etc.)
- **No Duplication**: Each flash message is only processed once

## Testing
To verify the fix:

1. Navigate to Admin > Roles
2. Try to delete a role that has users assigned (e.g., "demo" or "user" role)
3. Confirm deletion in the dialog
4. **Expected**: Only one error toast appears
5. **Previous**: Two identical error toasts appeared

## Notes
- Create and Edit pages still use `useToast()` legitimately for both manual toasts and flash message handling
- The layout's automatic flash message handling remains intact
- Loading toasts for deletion operations continue to work as expected
