<?php

namespace Melody\Views;

abstract class View {

    public function __construct($properties) {
        foreach ($properties as $name => $value) {
            $this->$name = $value;
        }
    }

    public abstract function render();
}