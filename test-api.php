<?php
/**
 * Test GitHub API connection
 * This file helps diagnose API connection issues
 *
 * @package CL_MD_Obsidian_Reader
 */

// Load configuration.
require_once __DIR__ . '/config.php';

echo '<h1>GitHub API Test</h1>';
echo '<h2>Configuration</h2>';
echo '<p><strong>Repository:</strong> ' . htmlspecialchars( CL_GITHUB_REPO ) . '</p>';
echo '<p><strong>Token configured:</strong> ' . ( ! empty( CL_GITHUB_TOKEN ) ? 'Yes (length: ' . strlen( CL_GITHUB_TOKEN ) . ')' : 'No' ) . '</p>';

// Test API URL.
$url = 'https://api.github.com/repos/' . CL_GITHUB_REPO . '/contents';

echo '<h2>Testing API Connection</h2>';
echo '<p><strong>URL:</strong> ' . htmlspecialchars( $url ) . '</p>';

// Prepare headers.
$headers = array(
	'User-Agent: PHP',
	'Accept: application/vnd.github.v3+json',
);

if ( ! empty( CL_GITHUB_TOKEN ) ) {
	$headers[] = 'Authorization: token ' . CL_GITHUB_TOKEN;
}

$options = array(
	'http' => array(
		'method'        => 'GET',
		'header'        => implode( "\r\n", $headers ),
		'ignore_errors' => true,
	),
);

$context  = stream_context_create( $options );
$response = file_get_contents( $url, false, $context );

echo '<h2>Response</h2>';

// Show HTTP response code.
if ( isset( $http_response_header ) ) {
	echo '<h3>HTTP Headers:</h3>';
	echo '<pre>';
	foreach ( $http_response_header as $header ) {
		echo htmlspecialchars( $header ) . "\n";
	}
	echo '</pre>';
}

// Show response body.
if ( false !== $response ) {
	echo '<h3>Response Body:</h3>';
	$data = json_decode( $response, true );
	echo '<pre>';
	print_r( $data );
	echo '</pre>';

	if ( isset( $data['message'] ) ) {
		echo '<p style="color: red; font-weight: bold;">Error: ' . htmlspecialchars( $data['message'] ) . '</p>';

		if ( isset( $data['documentation_url'] ) ) {
			echo '<p>Documentation: <a href="' . htmlspecialchars( $data['documentation_url'] ) . '" target="_blank">' . htmlspecialchars( $data['documentation_url'] ) . '</a></p>';
		}
	} else {
		echo '<p style="color: green; font-weight: bold;">✓ API connection successful!</p>';
		echo '<p>Found ' . count( $data ) . ' items in the repository root.</p>';
	}
} else {
	echo '<p style="color: red; font-weight: bold;">✗ Failed to connect to GitHub API</p>';
	$error = error_get_last();
	if ( $error ) {
		echo '<pre>';
		print_r( $error );
		echo '</pre>';
	}
}
