<?php
/**
 * HTTP Authentication Handler
 * Protects the application with HTTP Basic Authentication
 *
 * @package CL_MD_Obsidian_Reader
 */

// Load configuration.
if ( ! file_exists( __DIR__ . '/config.php' ) ) {
	http_response_code( 500 );
	die( 'Configuration file not found' );
}

require_once __DIR__ . '/config.php';

/**
 * Check HTTP Authentication
 *
 * @return bool True if authenticated, exits if not
 */
function cl_check_http_auth() {
	// Skip if authentication is disabled.
	if ( ! defined( 'CL_ENABLE_HTTP_AUTH' ) || ! CL_ENABLE_HTTP_AUTH ) {
		return true;
	}

	// Check if users are configured.
	if ( ! defined( 'CL_HTTP_AUTH_USERS' ) || empty( CL_HTTP_AUTH_USERS ) ) {
		return true;
	}

	$realm = defined( 'CL_HTTP_AUTH_REALM' ) ? CL_HTTP_AUTH_REALM : 'Restricted Area';
	$users = CL_HTTP_AUTH_USERS;

	// Check if authentication headers are present.
	if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) || ! isset( $_SERVER['PHP_AUTH_PW'] ) ) {
		cl_send_auth_header( $realm );
		return false;
	}

	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];

	// Validate credentials.
	if ( ! isset( $users[ $username ] ) || $users[ $username ] !== $password ) {
		cl_send_auth_header( $realm );
		return false;
	}

	// Authentication successful.
	return true;
}

/**
 * Send HTTP Authentication header and exit
 *
 * @param string $realm Authentication realm.
 */
function cl_send_auth_header( $realm ) {
	header( 'WWW-Authenticate: Basic realm="' . $realm . '"' );
	header( 'HTTP/1.0 401 Unauthorized' );
	echo '<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Autenticación Requerida</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			display: flex;
			justify-content: center;
			align-items: center;
			height: 100vh;
			margin: 0;
			background: #1a1a1a;
			color: #fff;
		}
		.container {
			text-align: center;
			padding: 2rem;
		}
		h1 { margin: 0 0 1rem; font-size: 2rem; }
		p { color: #999; }
	</style>
</head>
<body>
	<div class="container">
		<h1>🔒 Autenticación Requerida</h1>
		<p>Necesitas credenciales válidas para acceder a este contenido.</p>
	</div>
</body>
</html>';
	exit;
}

// Execute authentication check.
cl_check_http_auth();
