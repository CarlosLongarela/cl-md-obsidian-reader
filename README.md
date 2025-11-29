# Obsidian Repository Viewer

Visor web para repositorios de Obsidian en GitHub. Muestra archivos markdown con navegación tipo árbol.

## Características

- Navegación de archivos y carpetas tipo árbol
- Renderizado de markdown con soporte para sintaxis de Obsidian
- Caché de contenidos para mejor rendimiento
- Diseño responsive con tema oscuro
- Soporte para enlaces internos tipo wiki `[[link]]`
- Filtrado automático de archivos ocultos (que empiezan con punto)

## Requisitos

- PHP 7.0 o superior
- Servidor web (Apache, Nginx, etc.)
- Conexión a internet para acceder a la API de GitHub

## Instalación

1. Copia todos los archivos a tu servidor web
2. Asegúrate de que PHP esté habilitado
3. Abre `index.php` en tu navegador

## Configuración

### Repositorio GitHub

En `index.php` y `api.php`, modifica la variable del repositorio:

```php
$github_repo = 'CarlosLongarela/Obsidian-Carlos-Longarela';
```

### Token de GitHub (Opcional)

Para evitar límites de rate de la API, puedes añadir un token en `api.php`:

```php
'Authorization: token YOUR_GITHUB_TOKEN'
```

## Estructura de archivos

- `index.php` - Página principal con estructura HTML
- `styles.css` - Estilos CSS con variables personalizables
- `app.js` - Lógica JavaScript para navegación y renderizado
- `api.php` - Endpoint PHP para peticiones a la API de GitHub

## Personalización

### Colores

Modifica las variables CSS en `styles.css`:

```css
:root {
    --color-bg-primary: #1e1e1e;
    --color-accent: #7c3aed;
    /* etc... */
}
```

### Tamaños

```css
:root {
    --sidebar-width: 300px;
    --spacing-md: 1rem;
    /* etc... */
}
```

## Notas

- Los archivos markdown se procesan para convertir sintaxis específica de Obsidian
- Los enlaces tipo wiki `[[enlace]]` se convierten a enlaces navegables
- Solo se muestran archivos `.md` y carpetas (no archivos ocultos)
