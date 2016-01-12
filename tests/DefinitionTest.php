<?php

namespace GPS\Popov\Tests;

use GPS\Popov\Definition;

class DefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiate()
    {
        $def = new Definition('GPS\Popov\Tests\Example');
        $this->assertTrue($def instanceof Definition);
    }

    public function testCreatePlain()
    {
        $def = new Definition('GPS\Popov\Tests\Example');
        $def->setAttrs([
            'foo' => 'foo',
            'bar' => 'bar',
        ]);

        $instance = $def->create();

        $this->assertTrue($instance instanceof Example);
        $this->assertSame('foo', $instance->foo);
        $this->assertSame('bar', $instance->getBar());
    }

    public function testCreateWithCallable()
    {
        $def = new Definition('GPS\Popov\Tests\Example');
        $def->setAttrs([
            'foo' => function () { return 'foo'; },
            'bar' => function () { return 'bar'; },
        ]);

        $instance = $def->create();

        $this->assertTrue($instance instanceof Example);
        $this->assertSame('foo', $instance->foo);
        $this->assertSame('bar', $instance->getBar());
    }

    public function testCreateWithOverrides()
    {
        $def = new Definition('GPS\Popov\Tests\Example');
        $def->setAttrs([
            'foo' => function () { return time(); },
            'bar' => function () { return time(); },
        ]);

        $o1 = $def->create();
        $this->assertTrue(is_numeric($o1->foo));
        $this->assertTrue(is_numeric($o1->getBar()));

        $o2 = $def->create([
            'foo' => 'hello',
        ]);
        $this->assertSame('hello', $o2->foo);
    }

    public function testCreateWithInheritance()
    {
        $def = new Definition('GPS\Popov\Tests\ExtendedExample');
        $def->setAttrs([
            'foo' => function () { return time(); },
            'bar' => function () { return time(); },
            'priv' => function () { return 'wat'; },
        ]);

        $o = $def->create();
        $this->assertTrue($o instanceof ExtendedExample);
        $this->assertTrue(is_numeric($o->foo));
        $this->assertTrue(is_numeric($o->getBar()));
    }

    public function testAfter()
    {
        $def = new Definition('GPS\Popov\Tests\Example');
        $def->setAttrs([
            'foo' => 'foo',
            'bar' => 'bar',
        ])->after(function ($obj) {
            $obj->baz = 'baz';
        });

        $instance = $def->create();
        $def->finish($instance);

        $this->assertTrue($instance instanceof Example);
        $this->assertSame('foo', $instance->foo);
        $this->assertSame('bar', $instance->getBar());
        $this->assertSame('baz', $instance->baz);
    }
}

class Example
{
    public $foo;
    protected $bar;
    public $baz;
    public $rand;
    private $priv;

    public function getBar()
    {
        return $this->bar;
    }
}

class ExtendedExample extends Example
{
}
