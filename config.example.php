<?php
/**
 * Configuration file example for Obsidian Repository Viewer
 *
 * Copy this file to config.php and modify the values according to your needs
 */

// GitHub Repository Configuration.
define( 'CL_GITHUB_REPO', 'Username/Your-Obsidian-Repo' );

// GitHub API Token (optional).
// Generate a token at: https://github.com/settings/tokens
// Required scopes: repo (for private repos) or public_repo (for public repos only).
define( 'CL_GITHUB_TOKEN', '' );

// HTTP Authentication.
define( 'CL_ENABLE_HTTP_AUTH', false ); // Set to true to enable HTTP authentication.
define(
	'CL_HTTP_AUTH_USERS',
	array(
		'admin' => 'changeThisPassword',  // username => password.
		// 'user2' => 'anotherPassword',
		// Add more users as needed.
	)
);
define( 'CL_HTTP_AUTH_REALM', 'Obsidian Notes - Restricted Area' );

// Application Settings.
define( 'CL_APP_TITLE', 'Obsidian Notes' );
define( 'CL_DEFAULT_THEME', 'dark' ); // Options: 'dark', 'light'.
define( 'CL_CACHE_ENABLED', true );
define( 'CL_CACHE_DURATION', 3600 ); // Cache duration in seconds (1 hour).

// Display Settings.
define( 'CL_SHOW_FILE_EXTENSIONS', false ); // Show .md extension in file list.
define( 'CL_SIDEBAR_WIDTH', '300px' );
define( 'CL_MAX_FILE_SIZE', 1048576 ); // Maximum file size in bytes (1MB).

// Features.
define( 'CL_ENABLE_SEARCH', false ); // Enable search functionality (future feature).
define( 'CL_ENABLE_BREADCRUMBS', true ); // Show breadcrumb navigation.
define( 'CL_ENABLE_TOC', true ); // Enable table of contents for markdown files.

// API Rate Limiting.
define( 'CL_API_REQUESTS_PER_HOUR', 60 ); // GitHub API rate limit without token.
define( 'CL_API_REQUESTS_PER_HOUR_AUTH', 5000 ); // GitHub API rate limit with token.

// Debug Mode.
define( 'CL_DEBUG_MODE', false ); // Enable debug messages in console.

// Custom CSS (optional).
// Add custom CSS rules that will be injected into the page.
define( 'CL_CUSTOM_CSS', '' );
/* Example:
define( 'CL_CUSTOM_CSS', '
	.sidebar { background-color: #1a1a1a; }
	.content-body { font-size: 18px; }
' );
*/

// Excluded Paths
// Array of paths to exclude from the file tree (relative to repository root).
define(
	'CL_EXCLUDED_PATHS',
	array(
		'private',
		'drafts',
		'archive/old',
	)
);

// File Type Icons
// Customize icons for different file types or folders.
define(
	'CL_CUSTOM_ICONS',
	array(
		'folders' => array(
			'default'  => '📁',
			'images'   => '🖼️',
			'projects' => '📋',
			'archive'  => '📦',
		),
		'files'   => array(
			'default' => '📄',
			'index'   => '🏠',
			'readme'  => '📖',
			'todo'    => '✅',
		),
	)
);
