# Obsidian Repository Viewer

Visor web para repositorios de Obsidian en GitHub. Muestra archivos markdown con navegación tipo árbol.

## Características

- Navegación de archivos y carpetas tipo árbol
- Renderizado de markdown con soporte para sintaxis de Obsidian
- Caché de contenidos para mejor rendimiento
- Diseño responsive con tema oscuro/claro
- Soporte para enlaces internos tipo wiki `[[link]]`
- Filtrado automático de archivos ocultos
- **Autenticación HTTP opcional** para proteger el contenido
- Configuración centralizada mediante archivo `config.php`

## Requisitos

- PHP 7.4 o superior (recomendado PHP 8.0+)
- Servidor web (Apache, Nginx, etc.)
- Extensión PHP `mbstring` habilitada
- Conexión a internet para acceder a la API de GitHub

## Instalación

1. **Clona o descarga** el repositorio en tu servidor web
2. **Copia el archivo de configuración:**
   ```bash
   cp config.example.php config.php
   ```
3. **Edita `config.php`** y configura tus valores (ver sección siguiente)
4. **Asegúrate** de que PHP esté habilitado en tu servidor
5. **Accede** a `index.php` desde tu navegador

## Configuración

### Archivo config.php

El archivo `config.php` centraliza toda la configuración de la aplicación. **Nunca subas este archivo a tu repositorio público** (está incluido en `.gitignore`).

#### 1. Configuración GitHub (Obligatorio)

```php
// Repositorio a visualizar
define( 'CL_GITHUB_REPO', 'Usuario/Nombre-Repositorio' );

// Token de acceso personal de GitHub (REQUERIDO)
// Genera uno en: https://github.com/settings/tokens
// Permisos necesarios: Contents (Read-only), Metadata (Read-only)
define( 'CL_GITHUB_TOKEN', 'github_pat_...' );
```

#### 2. Autenticación HTTP (Opcional)

Protege tu aplicación con autenticación HTTP básica:

```php
// Habilitar autenticación HTTP
define( 'CL_ENABLE_HTTP_AUTH', true );

// Usuarios autorizados (usuario => contraseña)
define(
	'CL_HTTP_AUTH_USERS',
	array(
		'admin'  => 'contraseña_segura_123',
		'carlos' => 'otra_contraseña',
	)
);

// Mensaje del realm de autenticación
define( 'CL_HTTP_AUTH_REALM', 'Obsidian Notes - Área Restringida' );
```

#### 3. Configuración de la Aplicación

```php
define( 'CL_APP_TITLE', 'Mis Notas de Obsidian' );
define( 'CL_DEFAULT_THEME', 'dark' ); // 'dark' o 'light'
define( 'CL_ENABLE_BREADCRUMBS', true );
define( 'CL_SIDEBAR_WIDTH', '300px' );
```

Para ver todas las opciones disponibles, consulta `config.example.php`.

### Generar Token de GitHub

1. Ve a https://github.com/settings/tokens
2. Clic en **"Generate new token"** → **"Generate new token (classic)"** o usa **Fine-grained tokens**
3. Para tokens clásicos: marca el scope **`public_repo`** (o `repo` para repositorios privados)
4. Para tokens fine-grained:
   - Selecciona el repositorio específico
   - Agrega permisos: **Contents: Read-only** y **Metadata: Read-only**
5. Genera el token y **cópialo inmediatamente** (no podrás verlo después)
6. Pégalo en `config.php` en la constante `CL_GITHUB_TOKEN`

## Estructura de archivos

- `index.php` - Página principal con estructura HTML
- `api.php` - Endpoint PHP para peticiones a la API de GitHub
- `auth.php` - Manejador de autenticación HTTP
- `app.js` - Lógica JavaScript para navegación y renderizado
- `styles.css` - Estilos CSS con variables personalizables
- `config.php` - **Configuración (no incluido, créalo desde config.example.php)**
- `config.example.php` - Plantilla de configuración
- `test-api.php` - Script de diagnóstico para probar la conexión con GitHub

## Diagnóstico de Problemas

Si encuentras el error **"Error al cargar el repositorio"**, ejecuta el script de diagnóstico:

```
https://tu-dominio.com/ruta-proyecto/test-api.php
```

Este script te mostrará:
- Si el repositorio está configurado correctamente
- Si el token es válido y tiene los permisos adecuados
- El mensaje de error específico de la API de GitHub

### Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| **Bad credentials** | Token inválido o incompleto | Regenera el token y cópialo completo |
| **Not Found** | Repositorio no existe o sin acceso | Verifica el nombre del repo y los permisos del token |
| **Rate limit exceeded** | Demasiadas peticiones sin token | Configura un token válido |
| **Resource not accessible** | Token sin permisos | Agrega permisos Contents y Metadata |

## Personalización

### Colores y Temas

Modifica las variables CSS en `styles.css`:

```css
:root {
    --color-bg-primary: #1e1e1e;
    --color-accent: #7c3aed;
    /* etc... */
}
```

### Iconos Personalizados

En `config.php`:

```php
define(
	'CL_CUSTOM_ICONS',
	array(
		'folders' => array(
			'default'  => '📁',
			'projects' => '📋',
		),
		'files' => array(
			'default' => '📄',
			'readme'  => '📖',
		),
	)
);
```

### CSS Personalizado

Agrega CSS adicional directamente en `config.php`:

```php
define( 'CL_CUSTOM_CSS', '
	.sidebar { background-color: #1a1a1a; }
	.content-body { font-size: 18px; }
' );
```

## Seguridad

### Recomendaciones

1. **Nunca subas `config.php` a repositorios públicos** - Contiene tokens sensibles
2. **Usa contraseñas seguras** para la autenticación HTTP
3. **Considera usar HTTPS** en producción para proteger credenciales
4. **Regenera tokens** si sospechas que han sido expuestos
5. **Limita permisos del token** solo a lo necesario (read-only)

### Protección Adicional

- Agrega `config.php` a `.gitignore` (ya incluido)
- Usa variables de entorno para tokens en servidores de producción
- Considera implementar rate limiting adicional si es necesario

## Notas Técnicas

- Los archivos markdown se procesan para convertir sintaxis específica de Obsidian
- Los enlaces tipo wiki `[[enlace]]` se convierten a enlaces navegables
- Solo se muestran archivos `.md` y carpetas (archivos ocultos se filtran automáticamente)
- La codificación UTF-8 está forzada para soportar caracteres especiales (acentos, ñ, etc.)
- El contenido se obtiene de GitHub en formato base64 y se decodifica automáticamente

## Licencia

[Especifica tu licencia aquí]

## Autor

Carlos Longarela - [GitHub](https://github.com/CarlosLongarela)
