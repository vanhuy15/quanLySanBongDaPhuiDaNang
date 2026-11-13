<?php
namespace Core;

abstract class Controller {
    
    /**
     * Render a view with optional data
     */
    protected function render($view, $data = []) {
        extract($data);
        $file = __DIR__ . '/../views/' . $view . '.php';
        
        if (file_exists($file)) {
            require $file;
        } else {
            echo "View $view not found";
        }
    }

    /**
     * Render a view and return its content as string
     */
    protected function renderPartial($view, $data = []) {
        extract($data);
        $file = __DIR__ . '/../views/' . $view . '.php';
        if (file_exists($file)) {
            ob_start();
            require $file;
            return ob_get_clean();
        }
        return '';
    }
    
    /**
     * Redirect to another page
     */
    protected function redirect($url) {
        header("Location: " . $url);
        exit();
    }
    
    /**
     * Return JSON response
     */
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}
