<?php

namespace GPS\Popov\Tests;

use GPS\Popov\Facade;
use GPS\Popov\Factory;
use GPS\Popov\Pool;

class FacadeTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $f = Facade::instance();

        $this->assertTrue($f instanceof Factory);
    }

    public function testCreateCallable()
    {
        $f = Facade::instance();
        $f->definePool('Foo:GPS\Popov\Tests\Foo', 10);

        $callable = Facade::create('Foo');

        $this->assertTrue($callable instanceof \Closure);

        $obj = $callable();
        $this->assertTrue($obj instanceof Foo);
        $pool = $f->getPool('Foo');
        $this->assertTrue($pool instanceof Pool);

        $all = $pool->fetchAll();
        $this->assertSame(11, count($all));
    }
}

class Foo
{
}
