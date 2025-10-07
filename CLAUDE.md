# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview
This is a WordPress plugin called "Maquette char promo" developed by AtomikAgency. The plugin includes an automatic update system that checks for updates from a custom API endpoint.

## Architecture
- **Main Plugin File**: `src/maquette_char_promo.php` - Contains the plugin header and core initialization need only to include file. Not add code. 
- **Update System**: `src/update-checker.php` - Handles automatic plugin updates via custom API - Never update this file. 
- **Plugin ID**: `12017648-dc4c-4cfb-8914-912d0c0af47b` (used for update API calls) - Don't use it. 
- **Update Endpoint**: `https://plugin-manager.atomikagency.fr/api/pull/12017648-dc4c-4cfb-8914-912d0c0af47b` Never use it. 

## Key Constants
- `MAQUETTE_CHAR_PROMO_PLUGIN_DIR` - Plugin directory path
- `MAQUETTE_CHAR_PROMO_PLUGIN_URL` - Plugin URL

## Development Workflow

### Version Management
Plugin version is managed automatically via GitHub Actions. The version is automatically incremented on each push to main branch.

### Build Process
The plugin uses GitHub Actions for automated builds:
- Auto-increments version in `src/maquette_char_promo.php`
- Creates a zip package in `build/` directory
- Uploads to the AtomikAgency plugin manager API

### Manual Version Update
To manually update the plugin version, edit the `Version:` line in `src/maquette_char_promo.php`.

### Testing WordPress Plugin
Since this is a WordPress plugin, testing should be done in a WordPress environment:
- Install in a WordPress site's `wp-content/plugins/` directory
- Activate through WordPress admin
- Test update functionality by triggering WordPress update checks

## Important Notes
- The plugin uses a custom update system instead of WordPress.org repository
- All updates are served through AtomikAgency's plugin manager
- Direct file access is prevented with `ABSPATH` checks
- The update checker hooks into WordPress's `site_transient_update_plugins` filter

# Rules
The code need to be simple, we need to avoid object and do procédural code. Le code doit être accessible a un développeur de 2 ans d'XP.
Quand on créer de contenu de render html. On devra créer un template et l'user dans le code. ( usage ob_start).
Backend file will be in "include" folder.
Template files will be in "template" folder.
Shortcode files will be in "shortcode" folder