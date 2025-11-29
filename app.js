/**
 * Obsidian Repository Viewer - JavaScript Application
 */

class ObsidianViewer {
	constructor() {
		// Load configuration from globalThis.clConfig
		this.config       = globalThis.clConfig || {};
		this.githubRepo   = this.config.githubRepo || '';
		this.githubApiUrl = `https://api.github.com/repos/${this.githubRepo}/contents`;
		this.githubToken  = this.config.githubToken || '';
		this.currentPath  = '';
		this.cache        = new Map();

		this.init();
	}

	/**
	 * Initialize the application
	 */
	init() {
		this.attachEventListeners();
		this.setupMarkdownRenderer();
		this.setupThemeToggle();
		this.loadSavedTheme();
	}

	/**
	 * Setup theme toggle functionality
	 */
	setupThemeToggle() {
		const themeToggle = document.getElementById('themeToggle');
		if (themeToggle) {
			themeToggle.addEventListener('click', () => {
				this.toggleTheme();
			});
		}
	}

	/**
	 * Toggle between light and dark theme
	 */
	toggleTheme() {
		const html = document.documentElement;
		const currentTheme = html.dataset.theme;
		const newTheme     = currentTheme === 'dark' ? 'light' : 'dark';

		html.dataset.theme = newTheme;
		localStorage.setItem('cl_theme', newTheme);
	}

	/**
	 * Load saved theme from localStorage
	 */
	loadSavedTheme() {
		const savedTheme = localStorage.getItem('cl_theme');
		if (savedTheme) {
			document.documentElement.dataset.theme = savedTheme;
		}
	}

	/**
	 * Attach event listeners
	 */
	attachEventListeners() {
		const fileTree = document.getElementById('fileTree');

		fileTree.addEventListener('click', (event) => {
			const button = event.target.closest('.tree-button');
			if (!button) return;

			event.preventDefault();

			if (button.classList.contains('folder-button')) {
				this.toggleFolder(button);
			} else if (button.classList.contains('file-button')) {
				this.loadFile(button);
			}
		});
	}

	/**
	 * Setup markdown renderer options
	 */
	setupMarkdownRenderer() {
		if (typeof marked !== 'undefined') {
			marked.setOptions({
				breaks: true,
				gfm: true,
				headerIds: true,
				mangle: false,
				sanitize: false
			});
		}
	}

	/**
	 * Toggle folder open/closed
	 * @param {HTMLElement} button Folder button element
	 */
	async toggleFolder(button) {
		const path = button.dataset.path;
		const listElement = button.nextElementSibling;

		if (listElement.classList.contains('hidden')) {
			// Open folder
			button.classList.add('expanded');

			if (listElement.children.length === 0) {
				// Load contents if not already loaded
				await this.loadFolderContents(path, listElement);
			}

			listElement.classList.remove('hidden');
		} else {
			// Close folder
			button.classList.remove('expanded');
			listElement.classList.add('hidden');
		}
	}

	/**
	 * Load folder contents from GitHub API
	 * @param {string} path Folder path
	 * @param {HTMLElement} container Container element
	 */
	async loadFolderContents(path, container) {
		container.classList.add('loading');

		try {
			const headers = {
				'Accept': 'application/vnd.github.v3+json'
			};

			if (this.githubToken) {
				headers['Authorization'] = `token ${this.githubToken}`;
			}

			const response = await fetch(`${this.githubApiUrl}/${path}`, { headers });

			if (!response.ok) {
				throw new Error('Failed to load folder contents');
			}

			const contents = await response.json();
			const filtered = this.filterContents(contents, path);

			// Render folders first
			for (const folder of filtered.folders) {
				const li = this.createTreeItem('folder', folder);
				container.appendChild(li);
			}

			// Then render files
			for (const file of filtered.files) {
				const li = this.createTreeItem('file', file);
				container.appendChild(li);
			}

		} catch (error) {
			console.error('Error loading folder contents:', error);
			container.innerHTML = '<li class="error">Error loading contents</li>';
		} finally {
			container.classList.remove('loading');
		}
	}

	/**
	 * Filter and sort repository contents
	 * @param {Array} contents Raw contents from API
	 * @param {string} currentPath Current folder path
	 * @return {Object} Filtered contents
	 */
	filterContents(contents, currentPath = '') {
		const filtered = {
			folders: [],
			files: []
		};

		const excludedPaths = this.config.excludedPaths || [];

		for (const item of contents) {
			// Skip hidden files/folders
			if (item.name.startsWith('.')) continue;

			// Check if path is excluded
			const itemPath = currentPath ? `${currentPath}/${item.name}` : item.name;
			if (excludedPaths.some(excluded => itemPath.startsWith(excluded))) {
				continue;
			}

			if (item.type === 'dir') {
				filtered.folders.push(item);
			} else if (item.type === 'file' && item.name.endsWith('.md')) {
				filtered.files.push(item);
			}
		}

		// Sort alphabetically
		filtered.folders.sort((a, b) => a.name.localeCompare(b.name));
		filtered.files.sort((a, b) => a.name.localeCompare(b.name));

		return filtered;
	}

	/**
	 * Create tree item element
	 * @param {string} type Item type (folder/file)
	 * @param {Object} item Item data
	 * @return {HTMLElement} Tree item element
	 */
	createTreeItem(type, item) {
		const li = document.createElement('li');
		li.className = `tree-item ${type}`;

		const button = document.createElement('button');
		button.className = `tree-button ${type}-button`;
		button.dataset.path = item.path;

		const icon = document.createElement('span');
		icon.className = 'icon';

		// Get custom icon if available
		const customIcons = this.config.customIcons || {};
		if (type === 'folder') {
			const folderIcons = customIcons.folders || {};
			icon.textContent = folderIcons[item.name.toLowerCase()] || folderIcons.default || '📁';
		} else {
			const fileIcons = customIcons.files || {};
			const nameWithoutExt = item.name.replace('.md', '').toLowerCase();
			icon.textContent = fileIcons[nameWithoutExt] || fileIcons.default || '📄';
		}

		const name = document.createElement('span');
		name.className = 'name';

		// Show or hide file extension based on config
		if (type === 'file' && !this.config.showFileExtensions) {
			name.textContent = item.name.replace('.md', '');
		} else {
			name.textContent = item.name;
		}

		button.appendChild(icon);
		button.appendChild(name);
		li.appendChild(button);

		if (type === 'folder') {
			const subList = document.createElement('ul');
			subList.className = 'tree-list hidden';
			subList.dataset.path = item.path;
			li.appendChild(subList);
		}

		return li;
	}

	/**
	 * Load and display file content
	 * @param {HTMLElement} button File button element
	 */
	async loadFile(button) {
		const path = button.dataset.path;
		const fileName = button.querySelector('.name').textContent;

		// Update active state
		for (const btn of document.querySelectorAll('.tree-button')) {
			btn.classList.remove('active');
		}
		button.classList.add('active');

		// Update title
		document.getElementById('contentTitle').textContent = fileName;

		// Update breadcrumbs if enabled
		if (this.config.enableBreadcrumbs) {
			this.updateBreadcrumbs(path);
		}

		// Check cache
		if (this.cache.has(path)) {
			this.displayContent(this.cache.get(path));
			return;
		}

		// Load content
		const contentBody = document.getElementById('contentBody');
		contentBody.innerHTML = '<p class="loading">Cargando...</p>';

		try {
			const headers = {
				'Accept': 'application/vnd.github.v3+json'
			};

			if (this.githubToken) {
				headers['Authorization'] = `token ${this.githubToken}`;
			}

			const response = await fetch(`${this.githubApiUrl}/${path}`, { headers });

			if (!response.ok) {
				throw new Error('Failed to load file');
			}

			const data = await response.json();
			const content = atob(data.content);

			// Cache the content
			this.cache.set(path, content);

			// Display content
			this.displayContent(content);

		} catch (error) {
			console.error('Error loading file:', error);
			contentBody.innerHTML = '<p class="error">Error al cargar el archivo</p>';
		}
	}

	/**
	 * Update breadcrumbs navigation
	 * @param {string} path Current file path
	 */
	updateBreadcrumbs(path) {
		const breadcrumbs = document.getElementById('breadcrumbs');
		if (!breadcrumbs) return;

		breadcrumbs.style.display = 'block';
		breadcrumbs.innerHTML = '';

		// Home link
		const homeItem = document.createElement('span');
		homeItem.className = 'breadcrumb-item';
		const homeLink = document.createElement('a');
		homeLink.href = '#';
		homeLink.dataset.path = '';
		homeLink.textContent = 'Home';
		homeLink.addEventListener('click', (e) => {
			e.preventDefault();
			this.navigateToPath('');
		});
		homeItem.appendChild(homeLink);
		breadcrumbs.appendChild(homeItem);

		// Path segments
		const segments = path.split('/');
		let currentPath = '';

		for (let index = 0; index < segments.length; index++) {
			const segment = segments[index];
			if (index < segments.length - 1) {
				currentPath += (currentPath ? '/' : '') + segment;

				const item = document.createElement('span');
				item.className = 'breadcrumb-item';

				const link = document.createElement('a');
				link.href = '#';
				link.dataset.path = currentPath;
				link.textContent = segment;
				link.addEventListener('click', (e) => {
					e.preventDefault();
					this.navigateToPath(currentPath);
				});
				item.appendChild(link);
				breadcrumbs.appendChild(item);
			}
		}

		// Current file (no link)
		const currentItem       = document.createElement('span');
		currentItem.className   = 'breadcrumb-item';
		currentItem.textContent = segments.at(-1).replace('.md', '');
		breadcrumbs.appendChild(currentItem);
	}

	/**
	 * Navigate to a specific path in the tree
	 * @param {string} path Target path
	 */
	navigateToPath(path) {
		if (!path) {
			// Navigate to home - clear content
			document.getElementById('contentTitle').textContent = 'Selecciona un archivo';
			document.getElementById('contentBody').innerHTML = '<p class="empty-state">Navega por los archivos en el panel izquierdo para ver su contenido.</p>';
			document.getElementById('breadcrumbs').style.display = 'none';
			for (const btn of document.querySelectorAll('.tree-button')) {
				btn.classList.remove('active');
			}
			return;
		}
		// Find and expand folders to show the path
		const segments = path.split('/');
		let currentPath = '';

		for (const segment of segments) {
			currentPath += (currentPath ? '/' : '') + segment;
			const folderButton = document.querySelector(`.folder-button[data-path="${currentPath}"]`);
			if (folderButton && !folderButton.classList.contains('expanded')) {
				folderButton.click();
			}
		}
	}

	/**
	 * Display markdown content
	 * @param {string} content Markdown content
	 */
	displayContent(content) {
		const contentBody = document.getElementById('contentBody');

		// Process Obsidian-specific syntax
		const processedContent = this.processObsidianSyntax(content);

		// Render markdown
		if (typeof marked === 'undefined') {
			// Fallback if marked is not available
			contentBody.innerHTML = `<pre>${content}</pre>`;
		} else {
			const html = marked.parse(processedContent);
			contentBody.innerHTML = `<div class="markdown-content">${html}</div>`;

			// Process internal links
			this.processInternalLinks();
		}
	}

	/**
	 * Process Obsidian-specific syntax
	 * @param {string} content Original content
	 * @return {string} Processed content
	 */
	processObsidianSyntax(content) {
		// Convert Obsidian wiki links to markdown links
		content = content.replaceAll(/\[\[([^\]]+)\]\]/g, (match, link) => {
			const parts = link.split('|');
			const path = parts[0];
			const text = parts[1] || path;
			return `[${text}](${path}.md)`;
		});

		// Handle Obsidian callouts
		content = content.replaceAll(/^> \[!(\w+)\](.*)$/gm, (match, type, text) => {
			return `> **${type.toUpperCase()}**${text}`;
		});

		return content;
	}

	/**
	 * Process internal links in markdown content
	 */
	processInternalLinks() {
		const links = document.querySelectorAll('.markdown-content a');

		for (const link of links) {
			const href = link.getAttribute('href');

			// Check if it's an internal markdown link
			if (href?.endsWith('.md') && !href.startsWith('http')) {
				link.addEventListener('click', (event) => {
					this.navigateToFile(href);
				});
			}
		}
	}


	/**
	 * Navigate to internal file
	 * @param {string} path File path
	 */
	navigateToFile(path) {
		// Remove .md extension
		path = path.replace(/\.md$/, '');

		// Find the corresponding button in the tree
		const buttons = document.querySelectorAll('.file-button');

		for (const button of buttons) {
			const buttonPath = button.dataset.path;
			if (buttonPath.includes(path)) {
				button.click();
				break;
			}
		}
	}
}

// Initialize application when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
	new ObsidianViewer();
});
