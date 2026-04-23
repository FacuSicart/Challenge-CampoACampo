<?php

namespace App;

use App\Helpers\Response;

class Router
{
    private array $routes = [];

    /**
     * registra una ruta guardando el patrón para la URL y la función o método que tiene que ejecutarse
     */
    public function addRoute(string $method, string $pattern, callable $handler): void
    {
        $method = strtoupper($method);
        
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }
        
        $this->routes[$method][$pattern] = $handler;
    }

    /**
     * recibe el método y la URL del request, busca entre las rutas la que coincida y ejecuta el handler correspondiente;
     */
    public function handleRequest(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = parse_url($uri, PHP_URL_PATH);
        
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }

        if (!isset($this->routes[$method])) {
            Response::error("Método no permitido", 405);
            return;
        }
        
        foreach ($this->routes[$method] as $pattern => $handler) {
            $params = $this->matchRoute($pattern, $uri);
            
            if ($params !== null) {
                call_user_func_array($handler, $params);
                return;
            }
        }
        Response::error("Ruta no encontrada", 404);
    }

    /**
     * compara la URL que recibe con el patrón de la ruta, verifica que coincidan y si hay parámetros los extrae y los devuelve en un array.
     */
    private function matchRoute(string $pattern, string $uri): ?array
    {
        if (strpos($pattern, '{') === false) {
            return ($pattern === $uri) ? [] : null;
        }
        
        $patternParts = explode('/', trim($pattern, '/'));
        $uriParts = explode('/', trim($uri, '/'));
        
        if (count($patternParts) !== count($uriParts)) {
            return null;
        }
        
        $params = [];
        
        for ($i = 0; $i < count($patternParts); $i++) {
            $patternPart = $patternParts[$i];
            $uriPart = $uriParts[$i];
            
            if (preg_match('/^\{(.+)\}$/', $patternPart)) {
                $params[] = $uriPart;
            }

            elseif ($patternPart !== $uriPart) {
                return null;
            }
        }
        
        return $params;
    }
}
