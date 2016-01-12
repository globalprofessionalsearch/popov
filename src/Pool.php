<?php

namespace GPS\Popov;

/**
 * A fixed size collection of fixture instances.
 */
class Pool
{
    private $size;
    private $initialized = false;
    private $after;
    private $definition;
    private $factory;
    private $instances;

    public function __construct(Definition $definition, $size = null)
    {
        $this->definition = $definition;
        $this->size = $size;
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    public function setFactory($factory)
    {
        $this->factory = $factory;

        if ($this->definition) {
            $this->definition->setFactory($this->factory);
        }

        return $this;
    }

    public function after($callable)
    {
        if ($this->factory) {
            $callable->bindTo($this->factory);
        }

        $this->after = $callable;

        return $this;
    }

    public function fetchBy($field, $value)
    {
        $this->initialize();

        $result = $this->fetchMatching($this->createFieldMatcher($field, $value), 1);

        return (count($result) > 0) ? $result[0] : null;
    }

    public function fetchMultipleBy($field, $value, $max = null)
    {
        $this->initialize();

        return $this->fetchMatching($this->createFieldMatcher($field, $value), $max);
    }

    public function fetchRandom()
    {
        $this->initialize();

        if (0 == count($this->instances)) {
            return;
        }

        return $this->instances[mt_rand(0, count($this->instances) - 1)];
    }

    public function fetchMultipleRandom($num, $unique = true)
    {
        $this->initialize();

        if ($unique) {
            $map = [];

            while ($num > 0) {
                $obj = $this->fetchRandom();

                if (!isset($map[spl_object_hash($obj)])) {
                    $map[spl_object_hash($obj)] = $obj;
                    --$num;
                }
            }

            return array_values($map);
        }

        $matches = [];

        for ($i = 0; $i = $num; ++$i) {
            $matches[] = $this->fetchRandom();
        }

        return $matches;
    }

    public function fetchMatching($callable, $max = null)
    {
        $this->initialize();

        $matches = [];

        foreach ($this->instances as $obj) {
            if ($callable($obj)) {
                $matches[] = $obj;

                if ($max && count($matches) >= $max) {
                    return $matches;
                }
            }
        }

        return $matches;
    }

    public function fetchAll()
    {
        $this->initialize();

        return $this->instances;
    }

    public function create($overrides = [])
    {
        $this->initialize();

        $this->instances[] = $obj = $this->definition->create($overrides);

        if ($this->initialized) {
            $this->definition->resolveReferences($obj);
            $this->definition->finish($obj);
        }

        return $obj;
    }

    public function initialize()
    {
        if ($this->initialized) {
            return;
        }

        // needs to be marked initialzed immediatly to avoid infinite
        // loops where recursion triggers initialization multiple times
        $this->initialized = true;

        $this->instances = [];

        // immediately instantiate objects
        if ($this->size) {
            for ($i = 0; $i < $this->size; ++$i) {
                $this->instances[] = $this->definition->create();
            }
        }
    }

    public function initializeReferences()
    {
        $this->initialize();

        foreach ($this->instances as $obj) {
            $this->definition->resolveReferences($obj);
        }
    }

    public function finish()
    {
        $this->initialize();

        // call after hooks for each instance
        foreach ($this->instances as $obj) {
            $this->definition->finish($obj);
        }

        // then call after hook for entire pool
        if ($this->after) {
            call_user_func($this->after, $this);
        }
    }

    private function createFieldMatcher($field, $value)
    {
        $props = $this->definition->getReflProperties();

        if (!isset($props[$field])) {
            throw new \InvalidArgumentException(sprintf('No property found: %s', $field));
        }

        $prop = $props[$field];
        $matcher = function ($obj) use ($prop, $value) {
            return $value === $prop->getValue($obj);
        };

        return $matcher;
    }
}
