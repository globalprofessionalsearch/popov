# Popov #

[![Build Status](https://travis-ci.org/globalprofessionalsearch/popov.svg)](https://travis-ci.org/globalprofessionalsearch/popov)

> Plain Old Php Object Factory

This is a library for generating object graphs in PHP.  It's primarily used in conjunction with other libraries to generate data fixtures.  It does not include facilities for persisting data to a database; it's up to the user to determine the best method for doing so.

Why write this?

Many fixture libraries available make assumptions about the underlying storage mechanism, or are fairly complicated.  For some tests it may be enough to have access to objects that would have been fetched from the data store, without needing to involve that layer in the actual test.  But in an integration test you do want the database layer involved.  Popov lets you define your fixture models in a way that is relatively simple, but still useful in multiple contexts.  By not making assumptions about persistence, you are free to integrate this into your applications in whatever manner is the most suitable, and maintain full control.

## Installation ##

Add it to your project's composer.json:

```
{
    "require": {
        "globalprofessionalsearch/popov": "~1.0"
    }
}
```

## Usage ##

Below are usage examples, starting with the simplest, and increasing in complexity.  Since a fixture definition often requires the use of closures, and since Faker is so widely used for generating data points, we highly recommend using the `league/factory-muffin-faker` library in conjunction with Popov.  Most of the examples below use it, and you'll see that the use of facades greatly reduces the need to manually wrap field definitions in closures.

The examples below show defining pools of fictional classes that may be in an application.  It's assumed that these classes exist and provide getters/setters for the attributes described.  This is not a requirement - Popov will use reflection to set any public/protected/private properties defined.

Each example below adds to the previous one.

### Defining Pools ###

Let's assume we have users and groups, and we need to generate fake instances of each.  The example below defines two pools of objects with a preset number of instances in each.  When you define a pool, you provide the fully qualified class name (FQCN) to be used when creating the objects, and an option number of instances to be created when the pool is initialized.  Here we will define pools that generate 100 users and 20 groups:

```php
<?php

use GPS\Popov\Facade as Factory;
use League\FactoryMuffin\Faker\Facade as Faker;

// Returns an instance of `GPS\Popov\Factory`
$factory = Factory::instance();

// Define a pool of 100 users; `Factory::definePool` returns an instance 
// of `GPS\Popov\Definition`.  Defining a pool creates and stores an instance 
// of `GPS\Popov\Pool` in the factory.
$factory->definePool('App\User', 100)->setAttrs([

  //use the Faker facade to easily wrap calls to faker in closures...
  'firstName' => Faker::firstName(),
  'lastName' => Faker::lastName(),
  
  // ... or set explicit values, which will be the same for all objects
  'enabled' => true,
  
  // ... or reference any callable
  'address' => 'Some\Class::generateAddress',
  
  // ... or define custom closures to be called. Closures will receive the 
  // instance of the object that is being created
  'fullName' => function($user) {
    return $user->getFirstName().' '.$user->getLastName();
  }
]);

// Define a pool of 20 groups
$factory->definePool('App\Group', 20)->setAttrs([
  'number' => Faker::randomNumber()
]);

//  You can retrieve generated objects by accessing the created pools 
// directly...
$user1 = $factory->getPool('App\User')->fetchRandom();
$group1 = $factory->getPool('App\Group')->fetchRandom();

// ... or you can use the wrapper methods on the factory to interact with the
// underlying pool instances
$user2 = $factory->fetchRandom('App\User');
$group2 = $factory->fetchRandom('App\Group');
```

The first time an object is retrieved from the factory, the factory will initialize all defined pools in the order in which they were defined.

You can also define pools with an alias.  Doing this lets you avoid having to use the FQCN to reference the pool, but it also gives you the option of creating more than one pool for the same class if you need them to be configured differently.

```php
<?php

//...

$factory->definePool('User:MyApp\Models\User')->setAttrs([
  // ...
]);

$pool = $factory->getPool('User');

```

### Retrieving Objects ###

Once you have defined pools you can fetch created objects by using the api exposed by the `GPS\Popov\Pool`.  You can either fetch the pool directly from the factory, or use the wrapper methods provided by the factory.

The pools provide a few ways to fetch objects:

```php
$obj = $factory->getPool('App\User')->fetchRandom();
$objs = $factory->getPool('App\User')->fetchMultipleRandom();
$obj = $factory->getPool('App\User')->fetchBy('firstName', 'Foobert');
$objs = $factory->getPool('App\User')->fetchMultipleBy('isEnabled', true);
$objs = $factory->getPool('App\User')->fetchMatching(function ($obj) {
  // if the callable returns true, this object will be added 
  // to the returned set
  return in_array('Foobert', ['Alice','Bob', 'Foobert']);
});
$objs = $factory->getPool('App\User')->fetchAll();
```

### Nesting ###

To add to the example above, lets say that users also have account preferences, which is a nested object.  Any time a user is created, a nested object to hold their preferences should also be created.  We'll update the user definition above, and add a new one:

```php
<?php

// ...

$factory->definePool('App\User', 100)->setAttrs([
  'firstName' => Faker::firstName(),
  'lastName' => Faker::lastName(),
  'preferences' => Factory::create('App\AccountPrefs')
]);

// Note that we don't define a starting number for the pool.  In this case 
// there is no need since an instance will just be created any time a user is 
// created
$factory->definePool('App\AccountPrefs')->setAttrs([
  'allowEmails' => true,
]);

// the fetched user should contain the nested object
$allow = $factory
  ->fetchRandom('App\User')
  ->getPreferences()
  ->getAllowEmails()
;

```

### References ###

Nested objects are fairly straight forward, but sometimes you need objects from another pool, and you shouldn't trigger creating new ones.  For example, suppose we want all of our users to be in a group, but we don't want to create a new group for every user.  To do this, we use the factory to add a reference.  A reference is really the same thing as an attribute, but the factory resolves it *after* all pools have been initialized.  This allows you to fetch reference objects from other pools, including circular dependencies, without risking infinite loops.

To add a reference to an object in another pool, we'll just use the factory facade to wrap the action in a closure:

```php
<?php

//...

$factory
  ->definePool('App\User', 100)
  ->setAttrs([
    // ...
  ])
  ->setRefs([
    'groups' => Factory::fetchMultipleRandom('App\Group', 5)
  ])
;

$factory
  ->definePool('App\Group', 20)
  ->setAttrs([
    // ...
  ])
  ->setRefs([
    'owner' => Factory::fetchRandom('App\User')
  ])
;

```

### Hooks ###

Popov also provides a way to call a callback after each object has been created, and also after an entire pool has been initialized.

```php

//...

$factory
  ->definePool('App\User')
  ->setAttrs([
    // ...
  ])
  ->setRefs([
    // ...
  ])
  ->after(function($user) {
    // this will be called for each user after it's been created
    
    $user->doSomething();
  })
;

$factory
  ->getPool('App\User')
  ->after(function($pool) {
    // this will be called after the pool is
    // initialized
    foreach($pool->fetchAll() as $obj) {
      // ... do something
    }
  })
;

```

### Hard-coded objects ###

You may want 50 random objects in a pool, but you may also want a few objects that you can reference in tests which are defined explicitly by you, so you know up front what to expect.  If you have defined a pool, you can manually create new instances regardless of whether or not it will be automatically populated with a preset number.  Any manually created instances will just be added to the pool.

This has an important implication though - if you want to create a few explicit objects, you should do that *after* you have defined all of your pools.  Manual creation of an object will trigger initialization of all defined pools.

You can create a specific object by overriding the definition at create time:

```php
<?php

//... define some pools

// create a hard coded user... any attributes you provide
// will be merged with the existing attributes from the pool
$user = $factory->create('App\User', [
  'firstName' => 'Foobert',
  'lastName' => 'Bartleby',
  'preferences' => Factory::create('App\AccountPrefs', [
    'allowEmail' => false
  ])
]);

```

### Example with Persistence ###

Dealing with persistence can be fairly strait forward if you know the requirements for your app.  While Popov doesn't provide this out of the box, below is a simple example for how it could be done in an app that uses Doctrine.

```php
<?php

// ...

$factory->definePool('App\User', 100)->setAttrs([/* ... */]);
$factory->definePool('App\Group', 20)->setAttrs([/* ... */]);
$factory->definePool('App\Transaction', 50)->setAttrs([/* ... */]);

// ... maybe hard-code some example fixtures

// assuming we know that certain objects should be persisted in a certain order...
$persistOrder = [
  'App\User',
  'App\Group',
  'App\Transaction'
];

// use doctrine to persist each object in each pool
// in the needed order
foreach ($persistOrder as $poolName) {
  $pool = $factory->getPool($poolName);
  foreach ($pool->fetchAll() as $obj) {
    $doctrineManager->persist($obj);
  }

  $doctrineManager->flush();
}

```

## Contributing ##

Contributions are certainly welcome - just please adhere to the [PSR] coding standards.

### Testing ###

Run the tests with `vendor/bin/phpunit`.