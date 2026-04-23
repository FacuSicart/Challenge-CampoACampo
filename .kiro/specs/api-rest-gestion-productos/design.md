# Design Document: API REST Gestión de Productos

## Overview

Este documento describe el diseño técnico de una API REST para gestión de productos construida con PHP nativo (sin frameworks) que expone operaciones CRUD sobre un catálogo de productos almacenados en MySQL. La API implementa conversión automática de precios de ARS a USD mediante una tasa de cambio configurable por variable de entorno.

### Key Design Decisions

1. **Native PHP with Design Patterns**: Utilizamos PHP nativo implementando patrones MVC/ADR para separación de responsabilidades y Singleton para gestión de conexión a base de datos
2. **Docker-based Deployment**: Arquitectura de dos contenedores (PHP + MySQL) orquestados con docker-compose para portabilidad y consistencia
3. **JSON-only API**: Todas las respuestas y requests (POST/PUT) utilizan formato JSON con validación estricta de Content-Type
4. **Environment-based Configuration**: Parámetros críticos (conexión DB, tasa de cambio) configurables mediante variables de entorno
5. **Explicit Error Handling**: Códigos HTTP semánticos y mensajes de error estructurados en JSON para facilitar debugging

## Architecture

### High-Level Architecture

```
┌─────────────────┐
│   API Client    │
└────────┬────────┘
         │ HTTP/JSON
         ▼
┌─────────────────────────────────────┐
│     PHP Container (Apache/PHP)      │
│  ┌───────────────────────────────┐  │
│  │      Router (index.php)       │  │
│  └──────────┬────────────────────┘  │
│             │                        │
│  ┌──────────▼────────────────────┐  │
│  │    Controllers Layer          │  │
│  │  - ProductController          │  │
│  └──────────┬────────────────────┘  │
│             │                        │
│  ┌──────────▼────────────────────┐  │
│  │    Business Logic Layer       │  │
│  │  - ProductService             │  │
│  │  - PriceConverter             │  │
│  └──────────┬────────────────────┘  │
│             │                        │
│  ┌──────────▼────────────────────┐  │
│  │    Data Access Layer          │  │
│  │  - Database (Singleton)       │  │
│  │  - ProductRepository          │  │
│  └──────────┬────────────────────┘  │
└─────────────┼────────────────────────┘
              │ PDO/MySQL
              ▼
┌─────────────────────────────────────┐
│      MySQL Container                │
│  ┌───────────────────────────────┐  │
│  │   productos table             │  │
│  │   - id (PK, auto_increment)   │  │
│  │   - nombre (VARCHAR)          │  │
│  │   - descripcion (TEXT)        │  │
│  │   - precio (DECIMAL)          │  │
│  │   - created_at (TIMESTAMP)    │  │
│  │   - updated_at (TIMESTAMP)    │  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘
```

### Architectural Pattern: ADR (Action-Domain-Responder)

Implementamos una variante del patrón ADR adaptada a PHP nativo:

- **Action (Controller)**: Recibe HTTP request, valida input, invoca domain logic
- **Domain (Service/Repository)**: Contiene lógica de negocio y acceso a datos
- **Responder**: Formatea respuestas JSON con códigos HTTP apropiados

### Request Flow

1. Apache recibe HTTP request y lo enruta a `index.php`
2. Router analiza método HTTP y path, determina acción
3. Controller correspondiente valida request (Content-Type, parámetros, body)
4. Service layer ejecuta lógica de negocio (conversión de precios, validaciones)
5. Repository ejecuta operaciones de base de datos vía PDO
6. Service retorna datos al Controller
7. Controller formatea respuesta JSON con código HTTP apropiado
8. Apache envía respuesta al cliente

## Components and Interfaces

### 1. Router Component (`src/Router.php`)

**Responsibility**: Mapear HTTP requests a controllers específicos

```php
class Router {
    private array $routes = [];
    
    public function addRoute(string $method, string $pattern, callable $handler): void
    public function dispatch(string $method, string $uri): void
    public function extractParams(string $pattern, string $uri): array
}
```

**Key Methods**:
- `addRoute()`: Registra una ruta con su handler
- `dispatch()`: Analiza request y ejecuta handler correspondiente
- `extractParams()`: Extrae parámetros de URL (ej: `/productos/{id}`)

**Error Handling**:
- Retorna 404 si no encuentra ruta
- Retorna 405 si método HTTP no está permitido para la ruta

### 2. ProductController (`src/Controllers/ProductController.php`)

**Responsibility**: Manejar requests HTTP relacionados con productos

```php
class ProductController {
    private ProductService $service;
    
    public function __construct(ProductService $service)
    public function index(): void          // GET /productos
    public function show(int $id): void    // GET /productos/{id}
    public function store(): void          // POST /productos
    public function update(int $id): void  // PUT /productos/{id}
    public function destroy(int $id): void // DELETE /productos/{id}
}
```

**Validation Rules**:
- `store()`: Valida presencia de `nombre` y `precio`, valida que `precio` sea numérico positivo
- `update()`: Valida que al menos un campo esté presente, valida `precio` si se proporciona
- `show()/update()/destroy()`: Valida que `id` sea entero positivo

**Response Format**:
- Success: JSON con datos + código HTTP apropiado (200, 201)
- Error: JSON con estructura `{"error": "mensaje"}` + código HTTP (400, 404, 500)

### 3. ProductService (`src/Services/ProductService.php`)

**Responsibility**: Lógica de negocio para gestión de productos

```php
class ProductService {
    private ProductRepository $repository;
    private PriceConverter $converter;
    
    public function __construct(ProductRepository $repo, PriceConverter $conv)
    public function getAllProducts(): array
    public function getProductById(int $id): ?array
    public function createProduct(array $data): array
    public function updateProduct(int $id, array $data): ?array
    public function deleteProduct(int $id): bool
}
```

**Business Logic**:
- Enriquece productos con campo `precio_usd` usando PriceConverter
- Transforma datos de repository a formato de respuesta API
- Maneja casos de producto no encontrado retornando null

### 4. PriceConverter (`src/Services/PriceConverter.php`)

**Responsibility**: Convertir precios de ARS a USD

```php
class PriceConverter {
    private float $exchangeRate;
    
    public function __construct()
    public function convertToUSD(float $priceARS): float
    private function loadExchangeRate(): float
}
```

**Conversion Logic**:
- Lee `PRECIO_USD` de `$_ENV` o `getenv()`
- Valida que sea numérico positivo, lanza excepción si no lo es
- Calcula: `precio_usd = precio_ars / PRECIO_USD`
- Redondea resultado a 2 decimales usando `round($value, 2)`

**Error Handling**:
- Lanza `RuntimeException` si `PRECIO_USD` no está definida
- Lanza `RuntimeException` si `PRECIO_USD` no es numérico positivo

### 5. ProductRepository (`src/Repositories/ProductRepository.php`)

**Responsibility**: Acceso a datos de productos en MySQL

```php
class ProductRepository {
    private Database $db;
    
    public function __construct(Database $db)
    public function findAll(): array
    public function findById(int $id): ?array
    public function create(array $data): int
    public function update(int $id, array $data): bool
    public function delete(int $id): bool
}
```

**Database Operations**:
- `findAll()`: `SELECT * FROM productos ORDER BY id`
- `findById()`: `SELECT * FROM productos WHERE id = ?`
- `create()`: `INSERT INTO productos (nombre, descripcion, precio) VALUES (?, ?, ?)`
- `update()`: `UPDATE productos SET ... WHERE id = ?` (solo campos proporcionados)
- `delete()`: `DELETE FROM productos WHERE id = ?`

**Return Values**:
- `create()`: Retorna ID del nuevo registro (`lastInsertId()`)
- `update()/delete()`: Retorna `true` si afectó filas, `false` si no encontró registro
- `findById()`: Retorna array asociativo o `null` si no existe

### 6. Database Singleton (`src/Database/Database.php`)

**Responsibility**: Gestionar conexión única a MySQL

```php
class Database {
    private static ?Database $instance = null;
    private PDO $connection;
    
    private function __construct()
    public static function getInstance(): Database
    public function getConnection(): PDO
    private function connect(): PDO
}
```

**Connection Configuration** (from environment variables):
- `DB_HOST`: Hostname del servidor MySQL (default: `mysql`)
- `DB_PORT`: Puerto MySQL (default: `3306`)
- `DB_NAME`: Nombre de base de datos (default: `productos`)
- `DB_USER`: Usuario MySQL (default: `root`)
- `DB_PASSWORD`: Contraseña MySQL

**PDO Configuration**:
```php
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
```

### 7. Response Helper (`src/Helpers/Response.php`)

**Responsibility**: Estandarizar respuestas JSON

```php
class Response {
    public static function json(mixed $data, int $statusCode = 200): void
    public static function error(string $message, int $statusCode = 400): void
}
```

**Implementation**:
- Establece header `Content-Type: application/json; charset=UTF-8`
- Establece código HTTP con `http_response_code()`
- Codifica datos con `json_encode($data, JSON_UNESCAPED_UNICODE)`
- Termina ejecución con `exit()`

## Data Models

### Database Schema

```sql
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Field Specifications**:
- `id`: Auto-incremental, clave primaria
- `nombre`: Máximo 255 caracteres, obligatorio
- `descripcion`: Texto largo, opcional
- `precio`: Decimal con 10 dígitos totales y 2 decimales, almacena valor en ARS
- `created_at`: Timestamp automático en creación
- `updated_at`: Timestamp automático en creación y actualización

**Indexes**: Primary key en `id` (automático)

### API Data Model

**Product Response Object**:
```json
{
    "id": 1,
    "nombre": "Laptop Dell XPS 13",
    "descripcion": "Laptop ultraportátil con procesador Intel i7",
    "precio_ars": 850000.00,
    "precio_usd": 850.00,
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
}
```

**Product List Response**:
```json
[
    { /* Product Object */ },
    { /* Product Object */ }
]
```

**Error Response Object**:
```json
{
    "error": "Descripción del error"
}
```

**Success Message Response**:
```json
{
    "message": "Producto eliminado exitosamente"
}
```

### Data Transformation Flow

1. **Database → Repository**: PDO retorna array asociativo con campos de DB
2. **Repository → Service**: Array con campos: `id`, `nombre`, `descripcion`, `precio`, `created_at`, `updated_at`
3. **Service → Controller**: Array enriquecido con `precio_ars` (renombrado de `precio`) y `precio_usd` (calculado)
4. **Controller → Client**: JSON serializado del array enriquecido

## Error Handling

### Error Categories and HTTP Status Codes

| Error Type | HTTP Code | Trigger Condition | Response Format |
|------------|-----------|-------------------|-----------------|
| Validation Error | 400 | Campos requeridos faltantes, tipos inválidos | `{"error": "Campo 'nombre' es requerido"}` |
| Invalid JSON | 400 | Body no es JSON válido | `{"error": "JSON inválido en request body"}` |
| Invalid Content-Type | 415 | Content-Type no es application/json | `{"error": "Content-Type debe ser application/json"}` |
| Resource Not Found | 404 | ID de producto no existe | `{"error": "Producto no encontrado"}` |
| Route Not Found | 404 | Endpoint no existe | `{"error": "Ruta no encontrada"}` |
| Method Not Allowed | 405 | Método HTTP no soportado para ruta | `{"error": "Método no permitido"}` |
| Configuration Error | 500 | PRECIO_USD no definida o inválida | `{"error": "Error de configuración del servidor"}` |
| Database Error | 500 | Fallo de conexión o query | `{"error": "Error de base de datos"}` |

### Exception Handling Strategy

**Global Exception Handler** (`src/Handlers/ExceptionHandler.php`):
```php
class ExceptionHandler {
    public static function handle(Throwable $e): void {
        // Log error details
        error_log($e->getMessage() . "\n" . $e->getTraceAsString());
        
        // Determine HTTP code and user message
        if ($e instanceof ValidationException) {
            Response::error($e->getMessage(), 400);
        } elseif ($e instanceof NotFoundException) {
            Response::error($e->getMessage(), 404);
        } elseif ($e instanceof PDOException) {
            Response::error("Error de base de datos", 500);
        } else {
            Response::error("Error interno del servidor", 500);
        }
    }
}
```

**Custom Exceptions**:
- `ValidationException`: Para errores de validación de input
- `NotFoundException`: Para recursos no encontrados
- `ConfigurationException`: Para errores de configuración (PRECIO_USD, DB)

**Error Logging**:
- Todos los errores se registran en PHP error log con stack trace completo
- Mensajes al cliente son genéricos para errores 500 (no exponer detalles internos)
- Errores 4xx incluyen detalles específicos para ayudar al cliente

### Input Validation

**Request Body Validation** (POST /productos):
```php
function validateCreateRequest(array $data): void {
    if (!isset($data['nombre']) || trim($data['nombre']) === '') {
        throw new ValidationException("Campo 'nombre' es requerido");
    }
    
    if (!isset($data['precio'])) {
        throw new ValidationException("Campo 'precio' es requerido");
    }
    
    if (!is_numeric($data['precio']) || $data['precio'] <= 0) {
        throw new ValidationException("Campo 'precio' debe ser un número positivo");
    }
}
```

**URL Parameter Validation**:
```php
function validateId(string $id): int {
    if (!ctype_digit($id) || (int)$id <= 0) {
        throw new ValidationException("ID debe ser un entero positivo");
    }
    return (int)$id;
}
```

## Testing Strategy

### Testing Approach

Este proyecto utiliza una estrategia de testing basada en **tests de ejemplo** y **tests de integración**, ya que la naturaleza del sistema (API REST con operaciones CRUD sobre base de datos) no se beneficia de property-based testing. Las operaciones son principalmente:
- Interacciones con infraestructura externa (MySQL)
- Transformaciones HTTP request/response
- Validaciones de formato y reglas de negocio simples

**Why Property-Based Testing is NOT Appropriate**:
- Las operaciones CRUD son determinísticas y no tienen propiedades universales complejas
- El comportamiento depende fuertemente de estado de base de datos (side effects)
- La conversión de precios es una operación aritmética simple mejor validada con ejemplos específicos
- Los endpoints HTTP son mejor validados con casos de ejemplo concretos

### Test Categories

#### 1. Unit Tests (PHPUnit)

**PriceConverter Tests** (`tests/Unit/PriceConverterTest.php`):
- Conversión correcta con tasa válida (ejemplo: 1000 ARS con PRECIO_USD=1000 → 1.00 USD)
- Redondeo a 2 decimales (ejemplo: 1500 ARS con PRECIO_USD=1000 → 1.50 USD)
- Excepción cuando PRECIO_USD no está definida
- Excepción cuando PRECIO_USD no es numérico
- Excepción cuando PRECIO_USD es cero o negativo

**Validation Helper Tests** (`tests/Unit/ValidationTest.php`):
- Validación de ID: acepta enteros positivos, rechaza negativos, cero, strings no numéricos
- Validación de precio: acepta decimales positivos, rechaza negativos, cero, strings
- Validación de nombre: acepta strings no vacíos, rechaza strings vacíos o solo espacios

**Response Helper Tests** (`tests/Unit/ResponseTest.php`):
- Formato JSON correcto con UTF-8
- Headers correctos (Content-Type: application/json)
- Códigos HTTP correctos

#### 2. Integration Tests (PHPUnit con base de datos de prueba)

**ProductRepository Tests** (`tests/Integration/ProductRepositoryTest.php`):
- `findAll()`: retorna array vacío en DB vacía, retorna todos los productos cuando existen
- `findById()`: retorna producto existente, retorna null para ID inexistente
- `create()`: inserta producto y retorna ID, establece timestamps automáticamente
- `update()`: actualiza campos especificados, actualiza updated_at, retorna false para ID inexistente
- `delete()`: elimina producto existente, retorna false para ID inexistente

**Setup**: Usa base de datos MySQL en contenedor Docker con datos de prueba

#### 3. API Integration Tests (PHPUnit con HTTP requests)

**ProductController Tests** (`tests/Integration/ProductControllerTest.php`):

**GET /productos**:
- Retorna 200 con array vacío cuando no hay productos
- Retorna 200 con lista de productos incluyendo precio_ars y precio_usd
- Cada producto incluye todos los campos requeridos

**GET /productos/{id}**:
- Retorna 200 con producto existente incluyendo ambos precios
- Retorna 404 con mensaje de error para ID inexistente
- Retorna 400 para ID no numérico

**POST /productos**:
- Retorna 201 con producto creado cuando datos son válidos
- Retorna 400 cuando falta campo 'nombre'
- Retorna 400 cuando falta campo 'precio'
- Retorna 400 cuando precio no es numérico positivo
- Retorna 415 cuando Content-Type no es application/json
- Retorna 400 cuando body no es JSON válido

**PUT /productos/{id}**:
- Retorna 200 con producto actualizado cuando datos son válidos
- Retorna 404 para ID inexistente
- Retorna 400 cuando precio proporcionado no es numérico positivo
- Retorna 400 para ID no numérico
- Actualiza solo campos proporcionados, mantiene otros sin cambios

**DELETE /productos/{id}**:
- Retorna 200 con mensaje de éxito para producto existente
- Retorna 404 para ID inexistente
- Retorna 400 para ID no numérico

#### 4. Error Handling Tests

**Configuration Error Tests**:
- API retorna 500 cuando PRECIO_USD no está definida
- API retorna 500 cuando PRECIO_USD no es numérico válido

**Database Error Tests**:
- API retorna 500 cuando no puede conectar a MySQL
- Mensaje de error no expone detalles internos de DB

#### 5. Docker Infrastructure Tests

**Container Health Tests** (`tests/Infrastructure/DockerTest.php`):
- Contenedor PHP responde a health check
- Contenedor MySQL acepta conexiones
- Tabla productos existe con schema correcto
- Variables de entorno están configuradas correctamente

### Test Execution

**Local Development**:
```bash
# Levantar contenedores de prueba
docker-compose -f docker-compose.test.yml up -d

# Ejecutar suite completa
docker exec php-container vendor/bin/phpunit

# Ejecutar categoría específica
docker exec php-container vendor/bin/phpunit --testsuite=Unit
docker exec php-container vendor/bin/phpunit --testsuite=Integration
```

**CI/CD Pipeline**:
1. Build containers
2. Run unit tests (fast, no dependencies)
3. Run integration tests (con DB)
4. Run API tests (end-to-end)
5. Generate coverage report (objetivo: >80%)

### Test Data Management

**Fixtures** (`tests/Fixtures/ProductFixtures.php`):
```php
class ProductFixtures {
    public static function sampleProduct(): array {
        return [
            'nombre' => 'Producto de Prueba',
            'descripcion' => 'Descripción de prueba',
            'precio' => 1000.00
        ];
    }
    
    public static function invalidProducts(): array {
        return [
            ['nombre' => '', 'precio' => 100],      // nombre vacío
            ['nombre' => 'Test', 'precio' => -10],  // precio negativo
            ['nombre' => 'Test', 'precio' => 0],    // precio cero
            ['nombre' => 'Test', 'precio' => 'abc'] // precio no numérico
        ];
    }
}
```

**Database Seeding**:
- Script `tests/seed.php` para poblar DB de prueba con datos conocidos
- Cada test limpia y re-seed la DB para aislamiento

### Coverage Goals

- **Unit Tests**: >90% coverage de clases de lógica (PriceConverter, Validators)
- **Integration Tests**: 100% coverage de Repository methods
- **API Tests**: 100% coverage de Controller endpoints
- **Overall**: >80% code coverage

