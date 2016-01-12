<?php

namespace GPS\Popov\Tests;

use GPS\Popov\Factory;
use AC\ModelTraits\AutoGetterSetterTrait;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        mt_srand(1);
    }

    public function testInstantiate()
    {
        $f = new Factory();
        $this->assertTrue($f instanceof Factory);
    }

    public function testDefinePool()
    {
        $f = new Factory();
        $f->definePool('GPS\Popov\Tests\Example', 10);

        $objs = $f->getPool('GPS\Popov\Tests\Example')->fetchAll();
        $this->assertSame(10, count($objs));
    }

    public function testDefinePoolWithAlias()
    {
        $f = new Factory();
        $f->definePool('Example:GPS\Popov\Tests\Example', 10);

        $objs = $f->getPool('Example')->fetchAll();
        $this->assertSame(10, count($objs));
    }

    public function testGenerateGraph()
    {
        $f = new Factory();

        $f->definePool('GPS\Popov\Tests\User', 10)
            ->setAttrs([
                'id' => function () { return uniqid(); },
                'name' => function () { return 'wat'; },
                'email' => function () { return 'wat'; },
                'group' => function () use ($f) {
                    return $f->fetchRandom('GPS\Popov\Tests\Group');
                },
            ])
        ;

        $f->definePool('GPS\Popov\Tests\Group', 10)
            ->setAttrs([
                'id' => function () { return uniqid(); },
                'name' => function () { return 'wat'; },
            ])
        ;

        $obj = $f->fetchRandom('GPS\Popov\Tests\User');

        $this->assertTrue($obj->getGroup() instanceof Group);
    }

    public function testGenerateGraphWithCircularReferencesAndAfterHooks()
    {
        $test = $this;
        $assertions = function ($obj) use ($test) {
            $user = $obj->getGroup()->getOwner();
            $test->assertTrue($user instanceof User);
        };

        $f = new Factory();

        $f->definePool('GPS\Popov\Tests\User', 10)
            ->setAttrs([
                'id' => function () { return uniqid(); },
                'name' => function () { return 'wat'; },
                'email' => function () { return 'wat'; },
            ])
            ->setRefs([
                'group' => function () use ($f) {
                    return $f->fetchRandom('GPS\Popov\Tests\Group');
                },
            ])
            ->after(function ($obj) use ($assertions) {
                $assertions($obj);
            })
        ;

        $f->definePool('GPS\Popov\Tests\Group', 10)
            ->setAttrs([
                'id' => function () { return uniqid(); },
                'name' => function () { return 'wat'; },
            ])
            ->setRefs([
                'owner' => function () use ($f) {
                    return $f->fetchRandom('GPS\Popov\Tests\User');
                },
            ])
        ;

        $obj = $f->fetchRandom('GPS\Popov\Tests\User');

        $assertions($obj);
    }
}

class User
{
    use AutoGetterSetterTrait;

    protected $id;
    protected $name;
    protected $email;
    protected $group;
}

class Group
{
    use AutoGetterSetterTrait;

    protected $id;
    protected $name;

    protected $owner;
    protected $members;
}
