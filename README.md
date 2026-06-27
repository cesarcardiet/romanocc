# Sistema de Gestión de Leyes - API

## Descripción del Proyecto

Este proyecto es un sistema de gestión y consulta de leyes que proporciona una API RESTful para acceder a información legal estructurada. El sistema permite a los usuarios registrarse, autenticarse y consultar leyes, títulos, capítulos, subcapítulos y artículos de manera eficiente.

### Características Principales

- **Autenticación por teléfono**: Sistema de login y registro usando número de teléfono
- **Gestión de usuarios**: Registro con aceptación de términos y condiciones
- **Consulta de leyes**: Listado completo de leyes disponibles
- **Estructura jerárquica**: Navegación por títulos, capítulos, subcapítulos y artículos
- **Búsqueda avanzada**: Búsqueda en todo el contenido legal
- **API RESTful**: Interfaz de programación bien documentada

### Tecnologías Utilizadas

- **Backend**: Laravel 11 (PHP 8.2+)
- **Base de Datos**: SQLite (configurable para MySQL/PostgreSQL)
- **Autenticación**: Laravel Sanctum
- **Validación**: Form Requests personalizados
- **Documentación**: OpenAPI/Swagger compatible

## Instalación y Configuración

### Requisitos Previos

- PHP 8.2 o superior
- Composer
- Node.js y NPM (para assets frontend)

### Pasos de Instalación

1. **Clonar el repositorio**
   ```bash
   git clone <repository-url>
   cd romanoc
   ```

2. **Instalar dependencias PHP**
   ```bash
   composer install
   ```

3. **Configurar variables de entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurar base de datos**
   ```bash
   # Para SQLite (por defecto)
   touch database/database.sqlite
   
   # O configurar MySQL/PostgreSQL en .env
   ```

5. **Ejecutar migraciones**
   ```bash
   php artisan migrate
   ```

6. **Instalar dependencias frontend (opcional)**
   ```bash
   npm install
   npm run dev
   ```

7. **Iniciar servidor de desarrollo**
   ```bash
   php artisan serve
   ```

## Documentación de la API

### Base URL
```
http://localhost:8000/api
```

### Autenticación

La API utiliza Laravel Sanctum para la autenticación. Los endpoints protegidos requieren el header:
```
Authorization: Bearer {token}
```

### Endpoints

#### 1. Autenticación

##### Login
```http
POST /api/auth/login
```

**Parámetros:**
```json
{
    "phone": "1234567890",
    "password": "password123"
}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "message": "Login exitoso",
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Pérez",
            "phone": "1234567890",
            "type": "public",
            "status": "active"
        },
        "token": "1|abc123..."
    }
}
```

##### Registro
```http
POST /api/auth/register
```

**Parámetros:**
```json
{
    "name": "Juan Pérez",
    "phone": "1234567890",
    "password": "password123",
    "password_confirmation": "password123",
    "accepted_terms": true
}
```

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "message": "Usuario registrado exitosamente",
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Pérez",
            "phone": "1234567890",
            "type": "public",
            "status": "active"
        },
        "token": "1|abc123..."
    }
}
```

##### Logout
```http
POST /api/auth/logout
```

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "message": "Logout exitoso"
}
```

##### Perfil de Usuario
```http
GET /api/auth/profile
```

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Pérez",
            "phone": "1234567890",
            "type": "public",
            "status": "active"
        }
    }
}
```

#### 2. Leyes

##### Listar Leyes
```http
GET /api/laws
```

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "laws": [
            {
                "id": 1,
                "name": "Código Civil",
                "titles_count": 5,
                "chapters_count": 25,
                "created_at": "2025-01-27T10:00:00.000000Z",
                "updated_at": "2025-01-27T10:00:00.000000Z"
            }
        ]
    }
}
```

##### Obtener Detalles de Ley
```http
GET /api/laws/{id}
```

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Código Civil",
        "titles": [
            {
                "id": 1,
                "title": "Título I - De las Personas",
                "chapters": [
                    {
                        "id": 1,
                        "chapter": "Capítulo I - De las Personas Naturales",
                        "subchapters": [
                            {
                                "id": 1,
                                "subchapter": "Sección I - De la Personalidad",
                                "articles": [
                                    {
                                        "id": 1,
                                        "number": "1",
                                        "content": "La ley es obligatoria para todos los habitantes de la República..."
                                    }
                                ]
                            }
                        ]
                    }
                ]
            }
        ]
    }
}
```

#### 3. Búsqueda

##### Búsqueda Global
```http
GET /api/search?query={termino}
```

**Headers requeridos:**
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "data": {
        "query": "persona",
        "total_results": 15,
        "results": {
            "laws": [
                {
                    "type": "law",
                    "id": 1,
                    "name": "Código Civil",
                    "matched_content": "Código Civil"
                }
            ],
            "titles": [
                {
                    "type": "title",
                    "id": 1,
                    "title": "Título I - De las Personas",
                    "law_name": "Código Civil",
                    "law_id": 1,
                    "matched_content": "Título I - De las Personas"
                }
            ],
            "chapters": [],
            "subchapters": [],
            "articles": [
                {
                    "type": "article",
                    "id": 1,
                    "number": "1",
                    "content": "La ley es obligatoria para todos los habitantes de la República...",
                    "subchapter": "Sección I - De la Personalidad",
                    "chapter": "Capítulo I - De las Personas Naturales",
                    "title": "Título I - De las Personas",
                    "law_name": "Código Civil",
                    "law_id": 1,
                    "matched_content": "La ley es obligatoria para todos los habitantes de la República..."
                }
            ]
        }
    }
}
```

### Códigos de Error

| Código | Descripción |
|--------|-------------|
| 400 | Bad Request - Datos de entrada inválidos |
| 401 | Unauthorized - Token inválido o expirado |
| 404 | Not Found - Recurso no encontrado |
| 422 | Unprocessable Entity - Error de validación |
| 500 | Internal Server Error - Error interno del servidor |

### Ejemplos de Respuestas de Error

**Error de validación (422):**
```json
{
    "message": "Los datos proporcionados no son válidos.",
    "errors": {
        "phone": [
            "El número de teléfono es requerido."
        ],
        "password": [
            "La contraseña debe tener al menos 6 caracteres."
        ]
    }
}
```

**Error de autenticación (401):**
```json
{
    "message": "Unauthenticated."
}
```

## Estructura de la Base de Datos

### Tabla: users
- `id` - Identificador único
- `name` - Nombre del usuario
- `email` - Email (opcional)
- `phone` - Número de teléfono (único)
- `password` - Contraseña hasheada
- `accepted_terms` - Aceptación de términos (boolean)
- `type` - Tipo de usuario (enum)
- `status` - Estado del usuario (enum)
- `created_at` - Fecha de creación
- `updated_at` - Fecha de actualización

### Tabla: laws
- `id` - Identificador único
- `name` - Nombre de la ley
- `created_at` - Fecha de creación
- `updated_at` - Fecha de actualización

### Tabla: titles
- `id` - Identificador único
- `law_id` - Referencia a la ley
- `title` - Título
- `created_at` - Fecha de creación
- `updated_at` - Fecha de actualización

### Tabla: chapters
- `id` - Identificador único
- `title_id` - Referencia al título
- `chapter` - Capítulo
- `created_at` - Fecha de creación
- `updated_at` - Fecha de actualización

### Tabla: subchapters
- `id` - Identificador único
- `chapter_id` - Referencia al capítulo
- `subchapter` - Subcapítulo
- `created_at` - Fecha de creación
- `updated_at` - Fecha de actualización

### Tabla: articles
- `id` - Identificador único
- `subchapter_id` - Referencia al subcapítulo
- `number` - Número del artículo
- `content` - Contenido del artículo
- `created_at` - Fecha de creación
- `updated_at` - Fecha de actualización

## Desarrollo

### Ejecutar Tests
```bash
php artisan test
```

### Generar Documentación
```bash
# Si tienes L5-Swagger instalado
php artisan l5-swagger:generate
```

### Crear Migración
```bash
php artisan make:migration nombre_de_la_migracion
```

### Crear Controlador
```bash
php artisan make:controller Api/NombreController
```

## Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## Soporte

Para soporte técnico o preguntas sobre el proyecto, contacta al equipo de desarrollo.
