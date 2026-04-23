Requisitos

Docker 20.10 o superior
Docker Compose 2.0 o superior
Git (para clonar el repositorio)

No se necesita instalar PHP, MySQL ni Apache. Todo se ejecuta en Docker.

---
Configuración del Entorno

1. Clonar el Repositorio


git clone <URL_DEL_REPOSITORIO>
cd api-rest-gestion-productos


2. Configurar Variables de Entorno

Copia el archivo

- cp .env.example .env

Configurar Variable PRECIO_USD

La variable PRECIO_USD es obligatoria y define la el cambio para convertir pesos argentinos a dólares.

Archivo .env

env
Configuración de Base de Datos
DB_HOST=mysql
DB_PORT=3306
DB_NAME=productos_db
DB_USER=root
DB_PASSWORD=rootpassword

Tasa de Cambio (OBLIGATORIO)
PRECIO_USD=1450


Iniciar la Aplicación

1. Construir e Iniciar Contenedores

- docker-compose up -d

2. Verificar que los Contenedores Estén Corriendo

- docker-compose ps


3. Detener la Aplicación

- docker-compose down

4. Reiniciar la Aplicación

- docker-compose restart

---

Arquitectura

Cliente → Router → Controller → Service → Repository → DTO

Cliente, es el front, es quien envia la petición de lo que va a realizar el back
Router, es el encargado de mapear el metodo/url, busca el metodo, compara la url con los que tenemos registrados
Controller, es el que recibe la petición, este únicamente va a validar los datos y delegar la responsabilidad al service
Service, es el que contiene la lógica del negocio y va a ser el intermediario entre el controlador y los datos que vienen del repository
Repository, es el encargado de traducir a queries sql la lectura y escritura de datos
DTO, es el que gestiona la conexión, aca es donde se implementa singleton para utilizar una unica conexión a mysql

Agregados

Exception, división de errores segun sea el caso
ExceptionHandler, este va a burbujear el error que llega del controller al index para decidir el codigo HTTP que tiene que devolver
Response, Centraliza la respuesta HTTP garantizando que todas estas tengan el mismo formato y header sin repetir el código
PriceConverter, es quien va a pasar el precio en pesos a dolares

---

Probar la API

Opción 1: Frontend

Abrir el navegador e ir a:

http://localhost:8080


Opción solo para el back: cmd

1. Listar Productos

curl http://localhost:8080/productos

2. Crear Producto (completar)

curl -X POST http://localhost:8080/productos \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "",
    "descripcion": "",
    "precio": 
  }'

3. Obtener Producto por ID

curl http://localhost:8080/productos/ID

4. Actualizar Producto (completar)

curl -X PUT http://localhost:8080/productos/1 \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "",
    "precio": 
  }'

5. Eliminar un Producto

curl -X DELETE http://localhost:8080/productos/ID
