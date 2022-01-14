<?php

namespace Melody\Models;

use Database\DB;

abstract class Model {
    private static $currentBuilder;
    protected $attributes = [];

    protected $table = '';

    public static function find($id)
    {
        $emptyModel = new static;
        return static::from(DB::table($emptyModel->table)->where('id', '=', $id)->first());
    }

    public static function all()
    {
        $emptyModel = new static;
        return static::from(DB::table($emptyModel->table)->get());
    }

    public static function from($modelsArray = [])
    {
        $models = []; 
        foreach ($modelsArray as $attributes) {
            $models[] = static::create($attributes);
        }

        return $models;
    }

    public static function create($attributes = [])
    {
        return new static($attributes);
    }

    public function __construct($attributes = []) 
    {
        $this->attributes = $attributes;
    }

    public function __get($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
        $methodName = 'get' .ucfirst($name) .'Attribute';
        if (is_callable([$this, $methodName])) {
            return $this->$methodName();
        }

        return null;
    }

    public static function __callStatic($name, $arguments)
    {
        if (!static::$currentBuilder) {
            $emptyModel = new static;
            static::$currentBuilder = DB::model(static::class)->table($emptyModel->table);
        }
        if (is_callable([static::$currentBuilder, $name])) {
            return static::$currentBuilder->$name(...$arguments); 
        }
    }

}