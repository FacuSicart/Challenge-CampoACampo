<?php
require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

use App\Router;
use App\DTO\DTO;
use App\Repositories\ProductRepository;
use App\Services\PriceConverter;
use App\Services\ProductService;
use App\Controllers\ProductController;
use App\Handlers\ExceptionHandler;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

set_exception_handler([ExceptionHandler::class, 'handle']);

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $DTO = DTO::getInstance();
    $productRepository = new ProductRepository($DTO);
    $priceConverter = new PriceConverter();
    $productService = new ProductService($productRepository, $priceConverter);
    $productController = new ProductController($productService);
    
    // Inicializar router
    $router = new Router();
    
    $router->addRoute('GET', '/', function() {
        readfile(__DIR__ . '/frontend.html');
        exit();
    });

    $router->addRoute('GET', '/productos', [$productController, 'index']);
    $router->addRoute('GET', '/productos/{id}', [$productController, 'mostrar']);
    $router->addRoute('POST', '/productos', [$productController, 'crear']);
    $router->addRoute('PUT', '/productos/{id}', [$productController, 'actualizar']);
    $router->addRoute('DELETE', '/productos/{id}', [$productController, 'borrar']);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];

    $router->handleRequest($method, $uri);
    
} catch (Throwable $e) {
    ExceptionHandler::handle($e);
}
