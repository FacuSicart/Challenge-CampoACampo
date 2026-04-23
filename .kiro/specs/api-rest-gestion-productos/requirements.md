# Requirements Document

## Introduction

Este documento especifica los requisitos para una API REST de gestión de productos que permite operaciones CRUD sobre un catálogo de productos almacenados en MySQL. La API convierte automáticamente los precios de Pesos Argentinos (ARS) a Dólares estadounidenses (USD) utilizando una tasa de cambio configurable.

## Glossary

- **API**: La aplicación PHP que expone endpoints REST para gestión de productos
- **Database**: Sistema de base de datos MySQL que almacena la información de productos
- **Product**: Entidad que representa un producto con id, nombre, descripción, precio en ARS y timestamps
- **Price_Converter**: Componente que convierte precios de ARS a USD usando la tasa de cambio
- **Request**: Solicitud HTTP recibida por la API
- **Response**: Respuesta HTTP en formato JSON enviada por la API
- **PRECIO_USD**: Variable de entorno que contiene el valor actual del dólar para conversión

## Requirements

### Requirement 1: Database Schema

**User Story:** Como desarrollador, quiero una estructura de base de datos definida, para que los productos se almacenen de forma consistente.

#### Acceptance Criteria

1. THE Database SHALL contain a table named "productos"
2. THE productos table SHALL include a field "id" as primary key with auto-increment
3. THE productos table SHALL include a field "nombre" of type VARCHAR(255) that is NOT NULL
4. THE productos table SHALL include a field "descripcion" of type TEXT
5. THE productos table SHALL include a field "precio" of type DECIMAL(10,2) that stores values in ARS
6. THE productos table SHALL include a field "created_at" of type TIMESTAMP with default CURRENT_TIMESTAMP
7. THE productos table SHALL include a field "updated_at" of type TIMESTAMP with default CURRENT_TIMESTAMP and ON UPDATE CURRENT_TIMESTAMP

### Requirement 2: List All Products

**User Story:** Como cliente de la API, quiero listar todos los productos, para que pueda ver el catálogo completo con precios en ambas monedas.

#### Acceptance Criteria

1. WHEN a GET request is received at "/productos", THE API SHALL return all products from the Database
2. WHEN returning products, THE API SHALL include both "precio_ars" and "precio_usd" fields for each Product
3. WHEN calculating precio_usd, THE Price_Converter SHALL divide precio_ars by PRECIO_USD
4. THE Response SHALL use HTTP status code 200 for successful requests
5. THE Response SHALL format data as valid JSON
6. WHEN the productos table is empty, THE API SHALL return an empty array with HTTP status code 200

### Requirement 3: Get Product by ID

**User Story:** Como cliente de la API, quiero obtener los detalles de un producto específico, para que pueda consultar información detallada.

#### Acceptance Criteria

1. WHEN a GET request is received at "/productos/{id}", THE API SHALL return the Product with the matching id
2. WHEN returning a Product, THE API SHALL include both "precio_ars" and "precio_usd" fields
3. WHEN calculating precio_usd, THE Price_Converter SHALL divide precio_ars by PRECIO_USD
4. THE Response SHALL use HTTP status code 200 for successful requests
5. WHEN the requested id does not exist, THE API SHALL return HTTP status code 404
6. WHEN the requested id does not exist, THE Response SHALL include an error message in JSON format
7. WHEN the id parameter is not a valid integer, THE API SHALL return HTTP status code 400

### Requirement 4: Create New Product

**User Story:** Como cliente de la API, quiero crear nuevos productos, para que pueda agregar items al catálogo.

#### Acceptance Criteria

1. WHEN a POST request is received at "/productos", THE API SHALL create a new Product in the Database
2. THE API SHALL require "nombre" field in the Request body
3. THE API SHALL require "precio" field in the Request body
4. THE API SHALL accept optional "descripcion" field in the Request body
5. WHEN the Product is created successfully, THE API SHALL return HTTP status code 201
6. WHEN the Product is created successfully, THE Response SHALL include the created Product with its generated id
7. WHEN required fields are missing, THE API SHALL return HTTP status code 400
8. WHEN required fields are missing, THE Response SHALL include an error message describing missing fields
9. WHEN precio is not a valid positive number, THE API SHALL return HTTP status code 400
10. THE API SHALL store precio value in ARS in the Database

### Requirement 5: Update Existing Product

**User Story:** Como cliente de la API, quiero actualizar productos existentes, para que pueda modificar información del catálogo.

#### Acceptance Criteria

1. WHEN a PUT request is received at "/productos/{id}", THE API SHALL update the Product with the matching id
2. THE API SHALL accept "nombre" field in the Request body for update
3. THE API SHALL accept "descripcion" field in the Request body for update
4. THE API SHALL accept "precio" field in the Request body for update
5. WHEN the Product is updated successfully, THE API SHALL return HTTP status code 200
6. WHEN the Product is updated successfully, THE Response SHALL include the updated Product data
7. WHEN the requested id does not exist, THE API SHALL return HTTP status code 404
8. WHEN the id parameter is not a valid integer, THE API SHALL return HTTP status code 400
9. WHEN precio is provided and is not a valid positive number, THE API SHALL return HTTP status code 400
10. THE API SHALL update the updated_at timestamp automatically

### Requirement 6: Delete Product

**User Story:** Como cliente de la API, quiero eliminar productos, para que pueda remover items obsoletos del catálogo.

#### Acceptance Criteria

1. WHEN a DELETE request is received at "/productos/{id}", THE API SHALL remove the Product with the matching id from the Database
2. WHEN the Product is deleted successfully, THE API SHALL return HTTP status code 200
3. WHEN the Product is deleted successfully, THE Response SHALL include a success message in JSON format
4. WHEN the requested id does not exist, THE API SHALL return HTTP status code 404
5. WHEN the id parameter is not a valid integer, THE API SHALL return HTTP status code 400

### Requirement 7: Price Conversion Configuration

**User Story:** Como administrador del sistema, quiero configurar la tasa de cambio mediante variable de entorno, para que pueda actualizar el valor del dólar sin modificar código.

#### Acceptance Criteria

1. THE API SHALL read the PRECIO_USD value from environment variables
2. WHEN PRECIO_USD is not defined, THE API SHALL return HTTP status code 500 for any request requiring price conversion
3. WHEN PRECIO_USD is not a valid positive number, THE API SHALL return HTTP status code 500 for any request requiring price conversion
4. THE Price_Converter SHALL use PRECIO_USD as the divisor for ARS to USD conversion
5. THE API SHALL round precio_usd to 2 decimal places

### Requirement 8: Error Handling

**User Story:** Como cliente de la API, quiero recibir mensajes de error claros, para que pueda entender y corregir problemas en mis requests.

#### Acceptance Criteria

1. WHEN a database connection error occurs, THE API SHALL return HTTP status code 500
2. WHEN a database connection error occurs, THE Response SHALL include an error message in JSON format
3. WHEN invalid JSON is received in Request body, THE API SHALL return HTTP status code 400
4. WHEN an unsupported HTTP method is used, THE API SHALL return HTTP status code 405
5. WHEN an undefined route is accessed, THE API SHALL return HTTP status code 404
6. THE Response SHALL always include a "Content-Type: application/json" header

### Requirement 9: Docker Infrastructure

**User Story:** Como desarrollador, quiero contenedores Docker configurados, para que pueda desplegar la aplicación de forma consistente.

#### Acceptance Criteria

1. THE project SHALL include a Dockerfile for the PHP application container
2. THE project SHALL include a docker-compose.yml file that orchestrates both containers
3. THE docker-compose.yml SHALL define a service for the PHP application
4. THE docker-compose.yml SHALL define a service for MySQL database
5. THE MySQL container SHALL execute an initialization script that creates the productos table
6. THE docker-compose.yml SHALL configure PRECIO_USD as an environment variable for the PHP container
7. THE docker-compose.yml SHALL configure database connection parameters as environment variables
8. WHEN docker-compose up is executed, THE containers SHALL start and be ready to accept requests

### Requirement 10: JSON Request and Response Format

**User Story:** Como cliente de la API, quiero un formato JSON consistente, para que pueda integrar fácilmente con otros sistemas.

#### Acceptance Criteria

1. THE API SHALL accept Request body in JSON format for POST and PUT operations
2. THE API SHALL validate that Request Content-Type is "application/json" for POST and PUT operations
3. WHEN Content-Type is not "application/json" for POST or PUT, THE API SHALL return HTTP status code 415
4. THE Response SHALL always use "application/json" as Content-Type
5. THE Response JSON SHALL use UTF-8 encoding
6. WHEN returning a single Product, THE Response SHALL include fields: id, nombre, descripcion, precio_ars, precio_usd, created_at, updated_at
7. WHEN returning multiple Products, THE Response SHALL include an array of Product objects
