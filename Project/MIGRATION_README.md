# Migration: Groups and Tags Consolidation

This document explains the changes made to consolidate the previously separate "groups" and "tags" fields into a unified "categories" system.

## Changes Overview

1. **Database Schema**: 
   - Removed separate `user_groups` and `user_tags` tables
   - Created a unified `user_categories` table

2. **User Model**:
   - Removed separate `groups` and `tags` properties
   - Added a unified `categories` property
   - Updated methods to handle the consolidated data structure

3. **Command Handling**:
   - Updated command filtering to use `targetCategories` instead of separate `targetGroups` and `targetTags`
   - Ensured all related commands are sent together in a single operation

4. **UI Changes**:
   - Updated admin panel to show a single "Categories" field instead of separate "Groups" and "Tags" fields
   - Updated registration form to use "Categories" instead of "Tags"

## Migration Process

To migrate existing data from the old schema to the new schema:

1. Run the migration script:
   ```
   php config/migrate_to_categories.php
   ```

This script will:
- Create the new `user_categories` table if it doesn't exist
- Migrate data from `user_groups` to `user_categories`
- Migrate data from `user_tags` to `user_categories`
- Update existing commands to use `targetCategories` instead of separate `targetGroups` and `targetTags`

## Benefits

1. **Simplified Data Model**: A single unified categories system is easier to understand and maintain.
2. **Reduced Redundancy**: Eliminates the overlap between groups and tags.
3. **Improved Command Handling**: All related commands are now sent together in a single operation.
4. **Better User Experience**: Users now have a single field to manage their categories.

## Notes for Developers

- The User model now has a `categories` property instead of separate `groups` and `tags` properties.
- When filtering commands, use `targetCategories` instead of `targetGroups` and `targetTags`.
- Legacy code that still uses `groups` or `tags` will be handled by the User model's constructor, which converts them to categories.
- Active user sessions may need to log in again to see the changes. 