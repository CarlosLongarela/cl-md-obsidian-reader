<?php
/**
 * API endpoint for GitHub repository operations
 * Handles AJAX requests for repository content
 *
 * @package CL_MD_Obsidian_Reader
 */

// Ensure UTF-8 encoding.
mb_internal_encoding( 'UTF-8' );
mb_http_output( 'UTF-8' );

header( 'Content-Type: application/json; charset=utf-8' );
header( 'Access-Control-Allow-Origin: *' );

// Load configuration.
if ( ! file_exists( __DIR__ . '/config.php' ) ) {
	http_response_code( 500 );
	die(
		json_encode(
			array(
				'error'   => true,
				'message' => 'Configuration file not found. Please create config.php',
			)
		)
	);
}

require_once __DIR__ . '/config.php';

// Validate required configuration.
if ( ! defined( 'CL_GITHUB_REPO' ) || empty( CL_GITHUB_REPO ) ) {
	http_response_code( 500 );
	die(
		json_encode(
			array(
				'error'   => true,
				'message' => 'CL_GITHUB_REPO is not defined or empty in config.php',
			)
		)
	);
}

if ( ! defined( 'CL_GITHUB_TOKEN' ) || empty( CL_GITHUB_TOKEN ) ) {
	http_response_code( 500 );
	die(
		json_encode(
			array(
				'error'   => true,
				'message' => 'CL_GITHUB_TOKEN is not defined or empty in config.php',
			)
		)
	);
}

$github_api_base = 'https://api.github.com/repos/' . CL_GITHUB_REPO . '/contents';

// Get request parameters.
$action = isset( $_GET['action'] ) ? htmlspecialchars( $_GET['action'], ENT_QUOTES, 'UTF-8' ) : '';
$path   = isset( $_GET['path'] ) ? htmlspecialchars( trim( $_GET['path'] ), ENT_QUOTES, 'UTF-8' ) : '';

/**
 * Make GitHub API request
 *
 * @param string $url API URL.
 * @return array Response data
 */
function cl_github_request( $url ) {
	$headers = array(
		'User-Agent: PHP',
		'Accept: application/vnd.github.v3+json',
	);

	if ( defined( 'CL_GITHUB_TOKEN' ) && ! empty( CL_GITHUB_TOKEN ) ) {
		$headers[] = 'Authorization: token ' . CL_GITHUB_TOKEN;
	}

	$options = array(
		'http' => array(
			'header' => $headers,
		),
	);

	$context  = stream_context_create( $options );
	$response = file_get_contents( $url, false, $context );

	if ( false === $response ) {
		$error_message = 'Failed to fetch from GitHub API';

		if ( isset( $http_response_header ) ) {
			error_log( 'GitHub API Headers: ' . print_r( $http_response_header, true ) );

			// Check for specific HTTP errors.
			foreach ( $http_response_header as $header ) {
				if ( strpos( $header, 'HTTP/' ) === 0 ) {
					$error_message .= ' - ' . $header;
				}
			}
		}

		http_response_code( 500 );
		return array(
			'error'   => true,
			'message' => $error_message,
		);
	}

	$decoded = json_decode( $response, true );

	// Check if GitHub returned an error message.
	if ( isset( $decoded['message'] ) && ! isset( $decoded['name'] ) ) {
		http_response_code( isset( $decoded['documentation_url'] ) ? 403 : 400 );
		return array(
			'error'   => true,
			'message' => 'GitHub API: ' . $decoded['message'],
		);
	}

	return $decoded;
}

/**
 * Get repository contents
 *
 * @param string $path Repository path.
 * @return array Filtered contents
 */
function cl_get_contents( $path = '' ) {
	global $github_api_base;

	$url = $github_api_base;
	if ( ! empty( $path ) ) {
		$url .= '/' . $path;
	}

	$response = cl_github_request( $url );

	if ( isset( $response['error'] ) ) {
		return $response;
	}

	// Filter contents.
	$filtered = array(
		'folders' => array(),
		'files'   => array(),
	);

	foreach ( $response as $item ) {
		// Skip hidden files/folders.
		if ( strpos( $item['name'], '.' ) === 0 ) {
			continue;
		}

		if ( 'dir' === $item['type'] ) {
			$filtered['folders'][] = array(
				'name' => $item['name'],
				'path' => $item['path'],
				'type' => 'folder',
			);
		} elseif ( 'file' === $item['type'] && pathinfo( $item['name'], PATHINFO_EXTENSION ) === 'md' ) {
			$filtered['files'][] = array(
				'name' => str_replace( '.md', '', $item['name'] ),
				'path' => $item['path'],
				'type' => 'file',
				'size' => $item['size'],
			);
		}
	}

	// Sort alphabetically.
	usort(
		$filtered['folders'],
		function ( $a, $b ) {
			return strcasecmp( $a['name'], $b['name'] );
		}
	);

	usort(
		$filtered['files'],
		function ( $a, $b ) {
			return strcasecmp( $a['name'], $b['name'] );
		}
	);

	return $filtered;
}

/**
 * Get file content
 *
 * @param string $path File path.
 * @return array File content data
 */
function cl_get_file( $path ) {
	global $github_api_base;

	$url      = $github_api_base . '/' . $path;
	$response = cl_github_request( $url );

	if ( isset( $response['error'] ) ) {
		return $response;
	}

	// Decode base64 content.
	$content = base64_decode( $response['content'] );

	// Ensure UTF-8 encoding.
	if ( ! mb_check_encoding( $content, 'UTF-8' ) ) {
		$content = mb_convert_encoding( $content, 'UTF-8', 'auto' );
	}

	return array(
		'name'    => $response['name'],
		'path'    => $response['path'],
		'content' => $content,
		'size'    => $response['size'],
	);
}

// Route the request.
switch ( $action ) {
	case 'list':
		$result = cl_get_contents( $path );
		break;

	case 'file':
		if ( empty( $path ) ) {
			http_response_code( 400 );
			$result = array(
				'error'   => true,
				'message' => 'Path parameter is required',
			);
		} else {
			$result = cl_get_file( $path );
		}
		break;

	default:
		http_response_code( 400 );
		$result = array(
			'error'   => true,
			'message' => 'Invalid action',
		);
}

// Output JSON response.
echo json_encode( $result );
