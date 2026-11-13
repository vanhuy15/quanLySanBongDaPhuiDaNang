<?php
namespace Core;

class Router {
    protected $routes = [];

    public function add($route, $params = []) {
        $this->routes[$route] = $params;
    }

    public function dispatch($url) {
        $controllerName = isset($_GET['controller']) ? $_GET['controller'] : 'Home';
        $actionName = isset($_GET['action']) ? $_GET['action'] : 'index';

        // Sanitize controller and action names against XSS / Path Traversal
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $controllerName) || !preg_match('/^[a-zA-Z0-9_]+$/', $actionName)) {
            http_response_code(400);
            die("Lỗi 400: Tên controller hoặc action không hợp lệ.");
        }

        $controllerClass = 'Controllers\\' . $controllerName . 'Controller';

        if (class_exists($controllerClass)) {
            $controllerObject = new $controllerClass();

            if (is_callable([$controllerObject, $actionName])) {
                $controllerObject->$actionName();
            } else {
                http_response_code(404);
                $errorView = __DIR__ . '/../views/errors/404.php';
                if (file_exists($errorView)) {
                    require $errorView;
                } else {
                    $safeAction = htmlspecialchars($actionName, ENT_QUOTES, 'UTF-8');
                    $safeClass = htmlspecialchars($controllerClass, ENT_QUOTES, 'UTF-8');
                    echo "Lỗi 404: Phương thức {$safeAction} trong {$safeClass} không tồn tại.";
                }
            }
        } else {
            http_response_code(404);
            $errorView = __DIR__ . '/../views/errors/404.php';
            if (file_exists($errorView)) {
                require $errorView;
            } else {
                $safeClass = htmlspecialchars($controllerClass, ENT_QUOTES, 'UTF-8');
                echo "Lỗi 404: Controller {$safeClass} không tồn tại.";
            }
        }
    }
}
