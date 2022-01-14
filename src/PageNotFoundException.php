<?php

namespace Exceptions;

use Exception;

class PageNotFoundException extends Exception {
    protected $code = 404;
    private $route = '';

    public function __construct($route = '') {
        $this->route = $route;
        $this->message = 'Page not found';
    }

    public function getRoute() {
        return $this->route;
    }
}