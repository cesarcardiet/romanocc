# Documentación de APIs - Sistema de Gestión de Leyes

## Información General

- **Base URL**: `http://localhost:8000/api`
- **Autenticación**: Bearer Token (Laravel Sanctum)
- **Formato de respuesta**: JSON
- **Encoding**: UTF-8

## Autenticación

### Headers Requeridos para Endpoints Protegidos
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

## Endpoints

### 1. Autenticación

#### POST /api/auth/login
**Descripción**: Iniciar sesión con teléfono y contraseña

**Parámetros**:
```json
{
    "phone": "1234567890",
    "password": "password123"
}
```

**Respuesta exitosa (200)**:
```json
{
    "success": true,
    "message": "Login exitoso",
    "data": {
        "user": {
            "id": 1,
            "name": "Usuario de Prueba",
            "phone": "1234567890",
            "type": "public",
            "status": "active"
        },
        "token": "1|abc123def456..."
    }
}
```

**Errores posibles**:
- `422`: Datos de validación incorrectos
- `401`: Credenciales inválidas

---

#### POST /api/auth/register
**Descripción**: Registrar nuevo usuario

**Parámetros**:
```json
{
    "name": "Nuevo Usuario",
    "phone": "9876543210",
    "password": "password123",
    "password_confirmation": "password123",
    "accepted_terms": true
}
```

**Respuesta exitosa (201)**:
```json
{
    "success": true,
    "message": "Usuario registrado exitosamente",
    "data": {
        "user": {
            "id": 2,
            "name": "Nuevo Usuario",
            "phone": "9876543210",
            "type": "public",
            "status": "active"
        },
        "token": "2|xyz789abc123..."
    }
}
```

**Errores posibles**:
- `422`: Datos de validación incorrectos
- `409`: Teléfono ya registrado

---

#### POST /api/auth/logout
**Descripción**: Cerrar sesión (requiere autenticación)

**Headers**:
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200)**:
```json
{
    "success": true,
    "message": "Logout exitoso"
}
```

---

#### GET /api/auth/profile
**Descripción**: Obtener perfil del usuario autenticado

**Headers**:
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200)**:
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "Usuario de Prueba",
            "phone": "1234567890",
            "type": "public",
            "status": "active"
        }
    }
}
```

### 2. Leyes

#### GET /api/laws
**Descripción**: Listar todas las leyes disponibles (requiere autenticación)

**Headers**:
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200)**:
```json
{
    "success": true,
    "data": {
        "laws": [
            {
                "id": 1,
                "name": "Código Civil",
                "titles_count": 2,
                "chapters_count": 4,
                "created_at": "2025-01-27T10:00:00.000000Z",
                "updated_at": "2025-01-27T10:00:00.000000Z"
            },
            {
                "id": 2,
                "name": "Código Penal",
                "titles_count": 1,
                "chapters_count": 1,
                "created_at": "2025-01-27T10:00:00.000000Z",
                "updated_at": "2025-01-27T10:00:00.000000Z"
            }
        ]
    }
}
```

---

#### GET /api/laws/{id}
**Descripción**: Obtener detalles completos de una ley específica (requiere autenticación)

**Headers**:
```
Authorization: Bearer {token}
```

**Respuesta exitosa (200)**:
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
                                        "content": "La ley es obligatoria para todos los habitantes de la República, sin distinción de nacionalidad."
                                    },
                                    {
                                        "id": 2,
                                        "number": "2",
                                        "content": "La personalidad civil comienza con el nacimiento y termina con la muerte."
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

**Errores posibles**:
- `404`: Ley no encontrada

### 3. Búsqueda

#### GET /api/search?query={termino}
**Descripción**: Buscar en todas las leyes, títulos, capítulos, subcapítulos y artículos (requiere autenticación)

**Headers**:
```
Authorization: Bearer {token}
```

**Parámetros de query**:
- `query` (requerido): Término de búsqueda (mínimo 2 caracteres)

**Ejemplo de búsqueda**:
```
GET /api/search?query=persona
```

**Respuesta exitosa (200)**:
```json
{
    "success": true,
    "data": {
        "query": "persona",
        "total_results": 8,
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
            "chapters": [
                {
                    "type": "chapter",
                    "id": 1,
                    "chapter": "Capítulo I - De las Personas Naturales",
                    "title": "Título I - De las Personas",
                    "law_name": "Código Civil",
                    "law_id": 1,
                    "matched_content": "Capítulo I - De las Personas Naturales"
                }
            ],
            "subchapters": [
                {
                    "type": "subchapter",
                    "id": 1,
                    "subchapter": "Sección I - De la Personalidad",
                    "chapter": "Capítulo I - De las Personas Naturales",
                    "title": "Título I - De las Personas",
                    "law_name": "Código Civil",
                    "law_id": 1,
                    "matched_content": "Sección I - De la Personalidad"
                }
            ],
            "articles": [
                {
                    "type": "article",
                    "id": 3,
                    "number": "3",
                    "content": "Son personas todos los individuos de la especie humana, cualquiera que sea su edad, sexo, estirpe o condición.",
                    "subchapter": "Sección II - De la Capacidad",
                    "chapter": "Capítulo I - De las Personas Naturales",
                    "title": "Título I - De las Personas",
                    "law_name": "Código Civil",
                    "law_id": 1,
                    "matched_content": "Son personas todos los individuos de la especie humana, cualquiera que sea su edad, sexo, estirpe o condición."
                }
            ]
        }
    }
}
```

**Errores posibles**:
- `422`: Término de búsqueda inválido (menos de 2 caracteres)

## Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | OK - Solicitud exitosa |
| 201 | Created - Recurso creado exitosamente |
| 400 | Bad Request - Solicitud malformada |
| 401 | Unauthorized - No autenticado |
| 404 | Not Found - Recurso no encontrado |
| 409 | Conflict - Conflicto (ej: teléfono ya registrado) |
| 422 | Unprocessable Entity - Error de validación |
| 500 | Internal Server Error - Error interno del servidor |

## Ejemplos de Uso con cURL

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "phone": "1234567890",
    "password": "password123"
  }'
```

### Registro
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Nuevo Usuario",
    "phone": "9876543210",
    "password": "password123",
    "password_confirmation": "password123",
    "accepted_terms": true
  }'
```

### Listar Leyes
```bash
curl -X GET http://localhost:8000/api/laws \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### Buscar
```bash
curl -X GET "http://localhost:8000/api/search?query=persona" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

## Notas Importantes

1. **Autenticación**: Todos los endpoints excepto login y registro requieren autenticación
2. **Tokens**: Los tokens de Sanctum no expiran por defecto, pero se pueden configurar
3. **Validación**: Todos los datos de entrada son validados antes del procesamiento
4. **Respuestas**: Todas las respuestas siguen un formato consistente con `success` y `data`
5. **Búsqueda**: La búsqueda es case-insensitive y busca coincidencias parciales
6. **Paginación**: Actualmente no implementada, pero se puede agregar según necesidades

## Limitaciones Actuales

- No hay paginación en los resultados
- No hay filtros avanzados en la búsqueda
- No hay rate limiting configurado
- No hay cache implementado

## Próximas Mejoras Sugeridas

1. Implementar paginación en listados
2. Agregar filtros avanzados de búsqueda
3. Implementar cache para mejorar rendimiento
4. Agregar rate limiting
5. Implementar logs de auditoría
6. Agregar documentación OpenAPI/Swagger 