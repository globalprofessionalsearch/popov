<?php

namespace GPS\Popov;

/**
 * Defines attributes and references for creating instances of classes.
 */
class Definition
{
    private $initialized = false;
    private $class;
    private $constructor;
    private $attrs = [];
    private $refs = [];
    private $after;
    private $factory;
    private $reflProperties;

    /**
     *
     */
    public function __construct($class, $attrs = [], $constructor = null)
    {
        $this->class = $class;
        $this->attrs = $attrs;

        if (!$constructor) {
            $this->constructor = function () use ($class) { return new $class(); };
        }
    }

    public function setFactory($factory)
    {
        $this->factory = $factory;

        return $this;
    }

    public function setAttrs($attrs = [])
    {
        foreach ($attrs as $field => $attr) {
            $this->setAttr($field, $attr);
        }

        return $this;
    }

    public function setAttr($field, $def)
    {
        $this->attrs[$field] = $def;

        return $this;
    }

    public function setRef($field, $def)
    {
        $this->refs[$field] = $def;

        return $this;
    }

    public function setRefs($refs)
    {
        foreach ($refs as $field => $def) {
            $this->setRef($field, $def);
        }

        return $this;
    }

    public function create($overrides = [])
    {
        $this->initialize();

        $obj = call_user_func($this->constructor);

        $attrs = array_merge($this->attrs, $overrides);

        $this->applyAttributes($obj, $attrs);

        return $obj;
    }

    public function after($callable)
    {
        $this->after = $callable;

        return $this;
    }

    public function resolveReferences($obj)
    {
        $this->applyAttributes($obj, $this->refs);
    }

    public function finish($obj)
    {
        if ($this->after) {
            call_user_func($this->after, $obj);
        }
    }

    public function getReflProperties()
    {
        $this->initialize();

        return $this->reflProperties;
    }

    private function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $this->reflProperties = [];

        $reflClass = new \ReflectionClass($this->class);

        // index all available reflection properties
        $this->getReflectionProperties($reflClass, $this->reflProperties);

        $this->initialized = true;
    }

    private function getReflectionProperties($reflClass, &$props = [])
    {
        if ($parent = $reflClass->getParentClass()) {
            $this->getReflectionProperties($parent, $props);
        }

        foreach ($reflClass->getProperties() as $prop) {
            $prop->setAccessible(true);
            $props[$prop->getName()] = $prop;
        }
    }

    private function applyAttributes($obj, $attrs)
    {
        foreach ($attrs as $field => $def) {
            $rp = $this->reflProperties[$field];

            if ($def instanceof \Closure) {
                $value = $def($obj);

                $rp->setValue($obj, $value);
            } elseif (is_callable($def)) {
                $rp->setValue($obj, call_user_func($def));
            } else {
                $rp->setValue($obj, $def);
            }
        }
    }
}
