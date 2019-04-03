<?php

use \Firebase\JWT\JWT;

class Request {
    private static $instance;

    private $data;
    private $headers;
    private $route;

    // TODO: Populate headers property
    private function __construct() {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $this->data = $_GET;
        } else {
            if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
                $contentType = $_SERVER['HTTP_CONTENT_TYPE'];
                if (strpos($contentType, ';') !== false) {
                    $split = explode(';', $contentType);
                    $contentType = trim($split[0]);
                    $enc = trim($split[1]);
                }
                if ($contentType == 'application/x-www-form-urlencoded') {
                    $this->data = $_POST;
                } else if ($contentType == 'application/json') {
                    $json = file_get_contents('php://input');
                    $this->data = json_decode($json, true);
                }
            }
        }
        if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
            $this->route = explode('/', $_SERVER['REQUEST_URI']);
        }
    }

    /**
     * @return Request
     */
    static public function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new Request();
        }
        return self::$instance;
    }

    public function get($name, $default = null) {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return $default;
    }

    public function getAPIRoute() {
        return $this->route[2];
    }

    public function getObjectID() {
        return $this->route[3];
    }

    public function checkAuth() {
        $decoded = false;
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            $key = Config::get('auth/jwt_key');
            $token = str_replace('Bearer ', '', $authHeader);
            try {
                $decoded = JWT::decode($token, $key, array('HS256'));
            } catch (Exception $e) {
                $decoded = false;
            }
        }
        return $decoded;
    }
}
