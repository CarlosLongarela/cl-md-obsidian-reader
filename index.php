<?php
/**
 * Obsidian Repository Viewer
 * Displays markdown files from GitHub repository
 *
 * Requires config.php with repository settings
 *
 * @package ObsidianRepoViewer
 */

// Check HTTP Authentication.
require_once __DIR__ . '/auth.php';

// Load configuration.
if ( ! file_exists( __DIR__ . '/config.php' ) ) {
	http_response_code( 500 );
	echo '<h1>Error</h1>';
	echo '<h2>Archivo de configuración no encontrado. Por favor, crea config.php</h2>';
	echo '<p><em>Usa config.example.php como plantilla.</em></p>';
	die();
}

require_once __DIR__ . '/config.php';

// Validate required configuration.
if ( ! defined( 'CL_GITHUB_REPO' ) || empty( CL_GITHUB_REPO ) ) {
	http_response_code( 500 );
	echo '<h1>Error</h1>';
	echo '<h2>CL_GITHUB_REPO no está definido o está vacío en config.php</h2>';
	die();
}

if ( ! defined( 'CL_GITHUB_TOKEN' ) || empty( CL_GITHUB_TOKEN ) ) {
	http_response_code( 500 );
	echo '<h1>Error</h1>';
	echo '<h2>CL_GITHUB_TOKEN no está definido o está vacío en config.php</h2>';
	die();
}

$github_api_url = 'https://api.github.com/repos/' . CL_GITHUB_REPO . '/contents';

/**
 * Get repository contents from GitHub API
 *
 * @param string $path Path in repository.
 * @return array|false Repository contents or false on error.
 */
function cl_get_repo_contents( $path = '' ) {
	global $github_api_url;

	$url = $github_api_url;
	if ( ! empty( $path ) ) {
		$url .= '/' . $path;
	}

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
		error_log( 'GitHub API Error: Failed to fetch ' . $url );
		if ( isset( $http_response_header ) ) {
			error_log( 'Response headers: ' . print_r( $http_response_header, true ) );
		}
		return false;
	}

	$decoded = json_decode( $response, true );

	if ( isset( $decoded['message'] ) ) {
		error_log( 'GitHub API Response: ' . $decoded['message'] );
	}

	return $decoded;
}

/**
 * Filter and organize repository contents
 *
 * @param array $contents Raw contents from API.
 * @return array Filtered contents
 */
function cl_filter_contents( $contents ) {
	$filtered = array(
		'folders' => array(),
		'files'   => array(),
	);

	foreach ( $contents as $item ) {
		// Skip hidden files/folders (starting with dot).
		if ( strpos( $item['name'], '.' ) === 0 ) {
			continue;
		}

		if ( 'dir' === $item['type'] ) {
			$filtered['folders'][] = $item;
		} elseif ( 'file' === $item['type'] && pathinfo( $item['name'], PATHINFO_EXTENSION ) === 'md' ) {
			$filtered['files'][] = $item;
		}
	}

	// Sort folders and files alphabetically.
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

// Get initial contents.
$initial_contents  = cl_get_repo_contents();
$filtered_contents = $initial_contents ? cl_filter_contents( $initial_contents ) : false;
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?php echo CL_DEFAULT_THEME; ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars( CL_APP_TITLE ); ?></title>
	<link rel="stylesheet" href="styles.css">
	<?php if ( ! empty( CL_CUSTOM_CSS ) ) : ?>
	<style>
		<?php echo CL_CUSTOM_CSS; ?>
	</style>
	<?php endif; ?>
	<style>
		:root {
			--sidebar-width: <?php echo CL_SIDEBAR_WIDTH; ?>;
		}
	</style>
</head>
<body>
	<div class="sidebar-overlay" id="sidebarOverlay"></div>
	<div class="container">
		<aside class="sidebar" id="sidebar">
			<div class="sidebar-header">
				<h1><?php echo htmlspecialchars( CL_APP_TITLE ); ?></h1>
				<div class="header-buttons">
					<button class="theme-toggle" id="themeToggle" title="Cambiar tema">
						<span class="theme-icon-light">☀️</span>
						<span class="theme-icon-dark">🌙</span>
					</button>
					<button class="sidebar-close" id="sidebarClose" title="Cerrar menú">
						<span>✕</span>
					</button>
				</div>
			</div>
			<nav class="file-tree" id="fileTree">
				<?php if ( false === $filtered_contents ) : ?>
					<p class="error">Error al cargar el repositorio</p>
				<?php else : ?>
					<ul class="tree-list" data-path="">
						<?php foreach ( $filtered_contents['folders'] as $folder ) : ?>
							<li class="tree-item folder">
								<button class="tree-button folder-button" data-path="<?php echo htmlspecialchars( $folder['path'] ); ?>">
									<span class="icon">📁</span>
									<span class="name"><?php echo htmlspecialchars( $folder['name'] ); ?></span>
								</button>
								<ul class="tree-list hidden" data-path="<?php echo htmlspecialchars( $folder['path'] ); ?>"></ul>
							</li>
						<?php endforeach; ?>

						<?php foreach ( $filtered_contents['files'] as $file ) : ?>
							<li class="tree-item file">
								<button class="tree-button file-button" data-path="<?php echo htmlspecialchars( $file['path'] ); ?>">
									<span class="icon">📄</span>
									<span class="name"><?php echo htmlspecialchars( str_replace( '.md', '', $file['name'] ) ); ?></span>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</nav>
		</aside>

		<main class="content">
			<div class="content-header">
				<button class="mobile-menu-toggle" id="mobileMenuToggle" title="Abrir menú">
					<span></span>
					<span></span>
					<span></span>
				</button>
				<h2 id="contentTitle">Selecciona un archivo</h2>
				<button class="theme-toggle mobile-theme-toggle" id="mobileThemeToggle" title="Cambiar tema">
					<span class="theme-icon-light">☀️</span>
					<span class="theme-icon-dark">🌙</span>
				</button>
				<?php if ( CL_ENABLE_BREADCRUMBS ) : ?>
				<nav class="breadcrumbs" id="breadcrumbs" style="display: none;" aria-label="Breadcrumb navigation">
					<span class="breadcrumb-item">
						<a href="#" data-path="">Home</a>
					</span>
				</nav>
				<?php endif; ?>
			</div>
			<div class="content-body" id="contentBody">
				<p class="empty-state">Navega por los archivos en el panel izquierdo para ver su contenido.</p>
			</div>
		</main>
	</div>

	<script>
		// Pass configuration to JavaScript
		window.clConfig = {
			githubRepo: '<?php echo CL_GITHUB_REPO; ?>',
			githubToken: '<?php echo CL_GITHUB_TOKEN; ?>',
			showFileExtensions: <?php echo CL_SHOW_FILE_EXTENSIONS ? 'true' : 'false'; ?>,
			enableBreadcrumbs: <?php echo CL_ENABLE_BREADCRUMBS ? 'true' : 'false'; ?>,
			excludedPaths: <?php echo json_encode( CL_EXCLUDED_PATHS ); ?>,
			customIcons: <?php echo json_encode( CL_CUSTOM_ICONS ); ?>
		};
	</script>
	<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
	<script src="app.js"></script>
</body>
</html>
