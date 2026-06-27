# API V2 - ROMANOCC Mobile App

## 📋 Descripción

La API V2 está diseñada específicamente para la aplicación móvil ROMANOCC, manteniendo compatibilidad con el panel web existente. Esta versión retorna la estructura jerárquica exacta que espera la app móvil.

## 🔗 Endpoints

### **Leyes**

#### `GET /api/v2/laws`
Retorna todas las leyes con estructura jerárquica completa.

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "title": "TÍTULO I. DISPOSICIONES PRELIMINARES",
      "chapters": [
        {
          "chapter": "CAPÍTULO I. DISPOSICIONES GENERALES",
          "articles": [
            {
              "number": 1,
              "title": "ARTÍCULO 1. OBJETO DE LA LEY",
              "content": "La presente ley tiene por objeto establecer..."
            }
          ]
        }
      ]
    }
  ]
}
```

#### `GET /api/v2/laws/{id}`
Retorna una ley específica con estructura jerárquica.

#### `GET /api/v2/laws/{id}/detail`
Retorna información plana de la ley (para servicios).

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Ley General de Contrataciones Públicas",
    "description": "Ley que regula las contrataciones del Estado",
    "content": "Contenido completo de la ley...",
    "category": "ley",
    "file_url": null,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

### **Reglamentos**

#### `GET /api/v2/regulations`
Retorna todos los reglamentos con estructura jerárquica completa.

#### `GET /api/v2/regulations/{id}`
Retorna un reglamento específico con estructura jerárquica.

#### `GET /api/v2/regulations/{id}/detail`
Retorna información plana del reglamento (para servicios).

## 🏗️ Estructura de Datos

### **Estructura Jerárquica (para app móvil)**
```typescript
interface HierarchicalStructure {
  title: string;           // "TÍTULO I. DISPOSICIONES PRELIMINARES"
  chapters: {
    chapter: string;       // "CAPÍTULO I. DISPOSICIONES GENERALES"
    articles: {
      number: number;      // 1
      title: string;       // "ARTÍCULO 1. OBJETO DE LA LEY"
      content: string;     // "La presente ley tiene por objeto..."
    }[];
  }[];
}
```

### **Estructura Plana (para servicios)**
```typescript
interface FlatStructure {
  id: number;
  title: string;
  description: string;
  content: string;
  category: string;
  file_url?: string;
  created_at: string;
  updated_at: string;
}
```

## 🔄 Compatibilidad

### **Con App Móvil**
- ✅ Estructura jerárquica exacta
- ✅ Filtrado de búsqueda compatible
- ✅ Navegación a ArticleDetail
- ✅ Soporte para subcapítulos

### **Con Panel Web**
- ✅ No afecta endpoints existentes
- ✅ Mantiene estructura original
- ✅ Compatibilidad total

## 🚀 Implementación

### **Base de Datos**
```sql
-- Tabla laws con campo type
ALTER TABLE laws ADD COLUMN type VARCHAR(50) DEFAULT 'ley';

-- Actualizar registros existentes
UPDATE laws SET type = 'ley' WHERE type IS NULL;
```

### **Modelos**
```php
// Law.php
protected $fillable = ['name', 'type'];

public function scopeOfType($query, $type)
{
    return $query->where('type', $type);
}
```

### **Controladores**
- `LawControllerV2`: Maneja leyes (type = 'ley')
- `RegulationControllerV2`: Maneja reglamentos (type = 'reglamento')

## 📱 Uso en App Móvil

### **Configuración de API**
```typescript
// src/config/api.ts
export const API_CONFIG = {
  ENDPOINTS: {
    LAWS: '/api/v2/laws',
    LAW_DETAIL: '/api/v2/laws/:id/detail',
    REGULATIONS: '/api/v2/regulations',
    REGULATION_DETAIL: '/api/v2/regulations/:id/detail',
  }
};
```

### **Servicios**
```typescript
// src/services/lawService.ts
export const lawService = {
  getLaws(): Promise<ApiResponse<HierarchicalStructure[]>> {
    return httpClient.request(API_CONFIG.ENDPOINTS.LAWS);
  },

  getLawDetail(id: number): Promise<ApiResponse<FlatStructure>> {
    return httpClient.request(API_CONFIG.ENDPOINTS.LAW_DETAIL, {}, { id: String(id) });
  },
};
```

## 🔧 Testing

### **Endpoints de Prueba**
```bash
# Leyes
curl -X GET "http://localhost:8000/api/v2/laws" \
  -H "Authorization: Bearer {token}"

# Reglamentos
curl -X GET "http://localhost:8000/api/v2/regulations" \
  -H "Authorization: Bearer {token}"

# Detalle de ley
curl -X GET "http://localhost:8000/api/v2/laws/1/detail" \
  -H "Authorization: Bearer {token}"
```

## 📝 Notas Importantes

1. **Autenticación**: Todos los endpoints requieren token Bearer
2. **Estructura**: La app móvil espera exactamente la estructura jerárquica
3. **Ordenamiento**: Los artículos se ordenan por `article_number`
4. **Subcapítulos**: Se incluyen automáticamente en los artículos del capítulo padre
5. **Compatibilidad**: No afecta el funcionamiento del panel web existente

## 🎯 Próximos Pasos

1. ✅ Implementar controladores v2
2. ✅ Agregar rutas v2
3. ✅ Actualizar modelo Law
4. 🔄 Migrar datos existentes
5. 🔄 Actualizar app móvil para usar v2
6. 🔄 Testing completo
