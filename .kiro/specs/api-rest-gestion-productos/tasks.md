# Plan de Implementación: API REST Gestión de Productos

## Descripción General

Implementación de una API REST en PHP nativo para gestión de productos con operaciones CRUD, conversión automática de precios ARS a USD, y despliegue mediante Docker. La arquitectura sigue el patrón ADR con separación en capas (Router, Controllers, Services, Repositories).

## Tareas

- [-] 1. Configurar infraestructura Docker y base de datos
  - Crear Dockerfile para contenedor PHP con Apache
  - Crear docker-compose.yml con servicios PHP y MySQL
  - Crear script de inicialización SQL para tabla productos
  - Configurar variables de entorno (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, PRECIO_USD)
  - _Requisitos: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7, 9.8_

- [x] 2. Implementar capa de acceso a datos
  - [x] 2.1 Crear clase Database con patrón Singleton
    - Implementar conexión PDO a MySQL con configuración desde variables de entorno
    - Configurar PDO con ERRMODE_EXCEPTION y FETCH_ASSOC
    - _Requisitos: 8.1, 8.2_
  
  - [x] 2.2 Escribir tests unitarios para Database
    - Validar conexión exitosa con credenciales correctas
    - Validar excepción con credenciales incorrectas
    - Validar patrón Singleton (misma instancia)
  
  - [x] 2.3 Crear clase ProductRepository
    - Implementar método findAll() que retorna todos los productos
    - Implementar método findById() que retorna producto por ID o null
    - Implementar método create() que inserta producto y retorna ID generado
    - Implementar método update() que actualiza campos y retorna true/false
    - Implementar método delete() que elimina producto y retorna true/false
    - _Requisitos: 2.1, 3.1, 4.1, 5.1, 6.1_
  
  - [x] 2.4 Escribir tests de integración para ProductRepository
    - Test findAll() con base de datos vacía y con datos
    - Test findById() con ID existente e inexistente
    - Test create() verifica inserción y retorno de ID
    - Test update() verifica actualización de campos y updated_at
    - Test delete() verifica eliminación exitosa y caso ID inexistente
    - _Requisitos: 2.1, 3.1, 4.1, 5.1, 6.1_

- [x] 3. Implementar capa de lógica de negocio
  - [x] 3.1 Crear clase PriceConverter
    - Leer variable de entorno PRECIO_USD en constructor
    - Validar que PRECIO_USD sea numérico positivo, lanzar RuntimeException si no
    - Implementar método convertToUSD() que divide precio_ars por PRECIO_USD
    - Redondear resultado a 2 decimales
    - _Requisitos: 2.2, 2.3, 3.2, 3.3, 7.1, 7.2, 7.3, 7.4, 7.5_
  
  - [x] 3.2 Escribir tests unitarios para PriceConverter
    - Test conversión correcta con valores conocidos (1000 ARS / 1000 USD = 1.00)
    - Test redondeo a 2 decimales (1500 ARS / 1000 USD = 1.50)
    - Test excepción cuando PRECIO_USD no está definida
    - Test excepción cuando PRECIO_USD no es numérico
    - Test excepción cuando PRECIO_USD es cero o negativo
    - _Requisitos: 7.2, 7.3, 7.4, 7.5_
  
  - [x] 3.3 Crear clase ProductService
    - Implementar getAllProducts() que obtiene productos y agrega precio_usd
    - Implementar getProductById() que obtiene producto por ID y agrega precio_usd
    - Implementar createProduct() que valida datos y crea producto
    - Implementar updateProduct() que valida datos y actualiza producto
    - Implementar deleteProduct() que elimina producto
    - Transformar campo "precio" a "precio_ars" en respuestas
    - _Requisitos: 2.1, 2.2, 3.1, 3.2, 4.6, 5.6_
  
  - [x] 3.4 Escribir tests unitarios para ProductService
    - Test getAllProducts() enriquece productos con precio_usd
    - Test getProductById() retorna null para ID inexistente
    - Test createProduct() valida campos requeridos
    - Test updateProduct() maneja producto inexistente
    - _Requisitos: 2.1, 2.2, 3.1, 3.2_

- [-] 4. Checkpoint - Verificar capa de datos y lógica de negocio
  - Asegurarse de que todos los tests pasen, preguntar al usuario si surgen dudas.

- [x] 5. Implementar helpers y manejo de errores
  - [x] 5.1 Crear clase Response helper
    - Implementar método json() que establece Content-Type y codifica datos
    - Implementar método error() que retorna error en formato JSON
    - Usar JSON_UNESCAPED_UNICODE para caracteres especiales
    - _Requisitos: 8.6, 10.4, 10.5_
  
  - [x] 5.2 Crear excepciones personalizadas
    - Crear ValidationException para errores de validación (400)
    - Crear NotFoundException para recursos no encontrados (404)
    - Crear ConfigurationException para errores de configuración (500)
    - _Requisitos: 8.1, 8.2, 8.3, 8.4, 8.5_
  
  - [x] 5.3 Crear clase ExceptionHandler
    - Implementar manejador global que captura todas las excepciones
    - Mapear excepciones a códigos HTTP apropiados
    - Registrar errores en error_log con stack trace
    - Retornar mensajes genéricos para errores 500
    - _Requisitos: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_
  
- [ ] 6. Implementar capa de controladores
  - [x] 6.1 Crear clase ProductController
    - Implementar método index() para GET /productos
    - Implementar método show() para GET /productos/{id}
    - Implementar método store() para POST /productos
    - Implementar método update() para PUT /productos/{id}
    - Implementar método destroy() para DELETE /productos/{id}
    - _Requisitos: 2.1, 2.4, 2.5, 3.1, 3.4, 3.5, 4.1, 4.5, 5.1, 5.5, 6.1, 6.2_
  
  - [x] 6.2 Implementar validaciones en ProductController
    - Validar Content-Type application/json para POST y PUT
    - Validar que body sea JSON válido
    - Validar campos requeridos (nombre, precio) en store()
    - Validar que precio sea numérico positivo
    - Validar que ID sea entero positivo en show(), update(), destroy()
    - _Requisitos: 3.7, 4.2, 4.3, 4.7, 4.8, 4.9, 5.8, 5.9, 6.5, 10.1, 10.2, 10.3_
  
  - [x] 6.3 Implementar respuestas HTTP en ProductController
    - Retornar 200 para operaciones exitosas (GET, PUT, DELETE)
    - Retornar 201 para creación exitosa (POST)
    - Retornar 400 para errores de validación
    - Retornar 404 para recursos no encontrados
    - Retornar 415 para Content-Type inválido
    - Retornar 500 para errores de servidor
    - _Requisitos: 2.4, 2.6, 3.4, 3.5, 3.6, 4.5, 4.7, 5.5, 5.7, 6.2, 6.3, 8.1, 8.3, 8.4, 10.3_
  
  - [ ] 6.4 Escribir tests de integración para ProductController
    - Test GET /productos retorna 200 con array vacío o con productos
    - Test GET /productos/{id} retorna 200 con producto o 404
    - Test POST /productos retorna 201 con producto creado
    - Test POST /productos retorna 400 cuando faltan campos
    - Test POST /productos retorna 415 con Content-Type inválido
    - Test PUT /productos/{id} retorna 200 con producto actualizado
    - Test PUT /productos/{id} retorna 404 para ID inexistente
    - Test DELETE /productos/{id} retorna 200 con mensaje de éxito
    - Test DELETE /productos/{id} retorna 404 para ID inexistente
    - _Requisitos: 2.1, 2.4, 2.6, 3.1, 3.4, 3.5, 3.6, 4.5, 4.7, 5.5, 5.7, 6.2, 6.3_

- [x] 7. Implementar router y punto de entrada
  - [x] 7.1 Crear clase Router
    - Implementar método addRoute() para registrar rutas
    - Implementar método dispatch() que analiza URI y método HTTP
    - Implementar método extractParams() para extraer parámetros de URL
    - Manejar rutas no encontradas (404)
    - Manejar métodos no permitidos (405)
    - _Requisitos: 8.4, 8.5_
  
  - [x] 7.2 Crear archivo index.php como punto de entrada
    - Configurar manejo de errores con ExceptionHandler
    - Instanciar dependencias (Database, Repositories, Services, Controllers)
    - Configurar rutas en Router
    - Ejecutar dispatch() del Router
    - _Requisitos: 2.1, 3.1, 4.1, 5.1, 6.1_
  
  - [x] 7.3 Configurar .htaccess para reescritura de URLs
    - Redirigir todas las peticiones a index.php
    - Preservar query strings
    - _Requisitos: 2.1, 3.1, 4.1, 5.1, 6.1_
  
  - [x] 7.4 Escribir tests para Router
    - Test dispatch() ejecuta handler correcto para ruta válida
    - Test dispatch() retorna 404 para ruta inexistente
    - Test dispatch() retorna 405 para método no permitido
    - Test extractParams() extrae correctamente parámetros de URL
    - _Requisitos: 8.4, 8.5_

- [ ] 8. Checkpoint - Verificar integración completa
  - Asegurarse de que todos los tests pasen, preguntar al usuario si surgen dudas.

- [x] 9. Configurar estructura de proyecto y archivos de configuración
  - [x] 9.1 Crear estructura de directorios
    - Crear directorios: src/, src/Controllers/, src/Services/, src/Repositories/, src/Database/, src/Helpers/, src/Handlers/, tests/
    - _Requisitos: 9.1, 9.2_
  
  - [x] 9.2 Crear archivo .env.example
    - Documentar variables de entorno requeridas
    - Incluir valores de ejemplo para desarrollo
    - _Requisitos: 7.1, 9.6, 9.7_
  
  - [x] 9.3 Crear archivo README.md
    - Documentar requisitos del sistema
    - Documentar comandos de instalación y ejecución
    - Documentar endpoints de la API con ejemplos
    - Documentar variables de entorno
    - _Requisitos: 9.8_
  
  - [x] 9.4 Configurar PHPUnit para tests
    - Crear phpunit.xml con configuración de test suites
    - Configurar autoloading para tests
    - Configurar base de datos de prueba

- [x] 10. Validación final y documentación
  - [x] 10.1 Verificar formato de respuestas JSON
    - Validar que todas las respuestas incluyan Content-Type correcto
    - Validar que productos incluyan todos los campos requeridos
    - Validar que errores sigan formato consistente
    - _Requisitos: 10.4, 10.5, 10.6, 10.7_
  
  - [x] 10.2 Verificar manejo de casos edge
    - Validar comportamiento con base de datos vacía
    - Validar comportamiento con PRECIO_USD no definida
    - Validar comportamiento con JSON malformado
    - _Requisitos: 2.6, 7.2, 8.3_
  
  - [x] 10.3 Ejecutar suite completa de tests
    - Ejecutar tests unitarios
    - Ejecutar tests de integración
    - Verificar cobertura de código >80%

- [ ] 11. Checkpoint final - Verificación completa del sistema
  - Asegurarse de que todos los tests pasen, preguntar al usuario si surgen dudas.

## Notas

- Las tareas marcadas con `*` son opcionales y pueden omitirse para un MVP más rápido
- Cada tarea referencia requisitos específicos para trazabilidad
- Los checkpoints aseguran validación incremental del progreso
- Los tests unitarios validan lógica aislada, los tests de integración validan flujos completos
- La implementación sigue el orden de dependencias: infraestructura → datos → lógica → controladores → routing
