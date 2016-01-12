<?php

namespace GPS\Popov\tests;

use GPS\Popov\Definition;
use GPS\Popov\Pool;

class PoolTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        mt_srand(1);
    }

    private function createDefinition()
    {
        return (new Definition('GPS\Popov\Tests\Example'))
            ->setAttrs([
                'foo' => function () { return microtime(true); },
                'bar' => 'bar',
                'baz' => 'baz',
                'rand' => function () { return mt_rand(0, 1); },
            ])
        ;
    }

    public function testInstantiate()
    {
        $pool = new Pool($this->createDefinition(), 20);
        $this->assertTrue($pool instanceof Pool);
    }

    public function testFetchAll()
    {
        $pool = new Pool($this->createDefinition(), 20);
        $all = $pool->fetchAll();

        $this->assertSame(20, count($all));

        foreach ($all as $obj) {
            $this->assertTrue($obj instanceof Example);
        }
    }

    public function testCreateEmptyPool()
    {
        $pool = new Pool($this->createDefinition());

        $this->assertSame(0, count($pool->fetchAll()));
        $obj = $pool->create();
        $this->assertSame(1, count($pool->fetchAll()));
        $obj = $pool->create();
        $this->assertSame(2, count($pool->fetchAll()));
    }

    public function testFetchRandom()
    {
        $pool = new Pool($this->createDefinition(), 20);

        $o1 = $pool->fetchRandom();
        $o2 = $pool->fetchRandom();

        $this->assertFalse($o1 === $o2);
    }

    public function testFetchMultipleRandom()
    {
        $pool = new Pool($this->createDefinition(), 20);

        $objs = $pool->fetchMultipleRandom(3);

        $this->assertSame(3, count($objs));
    }

    public function testFetchBy()
    {
        $pool = new Pool($this->createDefinition(), 20);

        $obj = $pool->fetchBy('bar', 'bar');
        $this->assertSame('bar', $obj->getBar());

        $obj = $pool->fetchBy('rand', 1);
        $this->assertSame(1, $obj->rand);
    }

    public function testFetchMultipleBy()
    {
        $pool = new Pool($this->createDefinition(), 20);

        $objs = $pool->fetchMultipleBy('bar', 'bar');
        $this->assertSame(20, count($objs));
        foreach ($objs as $obj) {
            $this->assertSame('bar', $obj->getBar());
        }

        // with max limit
        $objs = $pool->fetchMultipleBy('bar', 'bar', 5);
        $this->assertSame(5, count($objs));
        $this->assertSame('bar', $obj->getBar());
        foreach ($objs as $obj) {
            $this->assertSame('bar', $obj->getBar());
        }

        $objs = $pool->fetchMultipleBy('bar', 'wrong');
        $this->assertSame(0, count($objs));

        $objs = $pool->fetchMultipleBy('rand', 1);
        foreach ($objs as $obj) {
            $this->assertSame(1, $obj->rand);
        }
    }

    public function testFetchMatching()
    {
        $pool = new Pool($this->createDefinition(), 20);

        $objs = $pool->fetchMatching(function ($obj) {
            return $obj->rand === 0;
        });

        foreach ($objs as $obj) {
            $this->assertSame(0, $obj->rand);
        }
    }
}
