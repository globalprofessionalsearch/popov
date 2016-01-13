<?php

namespace GPS\Popov;

/**
 * Manages collections of fixture pools and definitions, to help generate
 * complex graphs of objects.
 */
class Factory
{
    private $initialized = false;
    private $pools;

    public function definePool($name, $num = null)
    {
        $parts = explode(':', $name);
        $alias = $parts[0];
        if (count($parts) > 1) {
            $class = $parts[1];
        } else {
            $class = $parts[0];
        }

        if (!$class) {
            $class = $alias;
        }

        $this->pools[$alias] = $pool = new Pool(new Definition($class), $num);

        $pool->setFactory($this);

        return $pool->getDefinition();
    }

    public function getPool($name)
    {
        if (!isset($this->pools[$name])) {
            throw new \LogicException(sprintf('Requested unknown pool or definition: %s', $name));
        }

        $pool = $this->pools[$name];

        if (!$pool instanceof Pool) {
            throw new \LogicException(sprintf('No pool for %s, definition only.'), $name);
        }

        return $this->pools[$name];
    }

    public function getPools()
    {
        return $this->pools;
    }

    public function create($name, $overrides = [])
    {
        $this->initialize();

        return $this->getPool($name)->create($overrides);
    }

    public function fetchRandom($name)
    {
        $this->initialize();

        return $this->getPool($name)->fetchRandom();
    }

    public function fetchMultipleRandom($name, $num, $unique = true)
    {
        $this->initialize();

        return $this->getPool($name)->fetchMultipleRandom($num, $max, $unique);
    }

    public function fetchBy($name, $field, $value)
    {
        $this->initialize();

        return $this->getPool($name)->fetchBy($field, $value);
    }

    public function fetchMultipleBy($name, $field, $value, $max = null)
    {
        $this->initialize();

        return $this->getPool($name)->fetchMultipleBy($field, $value, $max);
    }
    
    public function fetchMatching($name, $callable, $max = null)
    {
        $this->initialize();

        return $this->getPool($name)->fetchBy($callable, $max);
    }

    public function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        // initialize pools
        foreach ($this->pools as $pool) {
            $pool->initialize();
        }

        // then allow pools to resolve potentially circular references
        foreach ($this->pools as $pool) {
            $pool->initializeReferences();
        }

        // then call any "after" hooks
        foreach ($this->pools as $pool) {
            $pool->finish();
        }
    }

    /**
     * A convenience method for creating closures around
     * any calls to the factory.
     */
    public function close($method, $args)
    {
        $factory = $this;

        return function () use ($factory, $method, $args) {
            return call_user_func_array([$factory, $method], $args);
        };
    }
}
