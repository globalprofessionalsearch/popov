# Popov #

> travis-ci badge

> Plain Old Php Object Factory

This is a library for generating object graphs in PHP.  It's primarily used in conjunction with other libraries to generate data fixtures.  It does not include facilities for persisting data to a database; it's up to the user to determine the best method for doing so.

Why write this?

Many fixture libraries available make assumptions about the underlying storage mechanism, or are fairly complicated.  For some tests it may be enough to have access to objects that would have been fetched from the data store, without needing to involve that layer in the actual test.  But in an integration test you do want the database layer involved.  Popov lets you define your fixture models in a way that is relatively simple, but still useful in multiple contexts.  By not making assumptions about persistence, you are free to integrate this into your applications in whatever manner is the most suitable, and maintain full control.

## Installation ##

Add it to your project with composer, and install:

```
composer ...
```

## Usage ##

Below are usage examples, starting with the simplest, and getting more complex.  Since a fixture definition often requires the use of closures, and since Faker is so widely used for generating data points, we highly recommend using the `league/factory-muffin-faker` library in conjunction with Popov.  Most of the examples below use it, and you'll see that the use of facades greatly reduces the need to manually wrap field definitions in closures.

Each example below adds to the previous one.

### Basic ###

Let's assume we have users and classes, and we need to generate fake instances of each.  The example below defines two pools of objects with a preset number of instances in each.  Here we will generate 100 users and 50 groups

```php
<?php

use GPS\Popov\Facade as Factory;
use League\FactoryMuffin\Faker\Facade as Faker;

// Returns an instance of `GPS\Popov\Factory`
$factory = Factory::instance();

// Define a pool of 100 users; `Factory::definePool` returns an instance of `GPS\Popov\Definition`
$factory->definePool('App\User', 100)->setAttrs([
  //use the Faker facade to easily wrap calls to faker in closures
  'firstName' => Faker::firstName(),
  'lastName' => Faker::lastName(),
  
  // or set explicit values, which will be the same for all objects
  'enabled' => true,
  
  // or define custom closures to be called
  'fullName' => function($user) {
    return $user->getFirstName().' '.$user->getLastName();
  }
]);

// Define a pool of 50 groups
$factory->definePool('App\Group', 50)->setAttrs([
  'number' => Faker::randomNumber()
]);

// You can retrieve generated objects by accessing the created pools directly...
$user1 = $factory->getPool('App\User')->fetchRandom();
$group1 = $factory->getPool('App\Group')->fetchRandom();

// ... or you can use the wrapper methods on the factory
$user2 = $factory->fetchRandom('App\User');
$group2 = $factory->fetchRandom('App\Group');
```

Once you retrieve an object from the factory, the factory will initialize all defined pools in the order in which they were defined.

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

// Note that we don't define a starting number for the pool.  In this case there is no need
// since an instance will just be created any time a user is created
$factory->definePool('App\AccountPrefs)->setAttrs([
  'allowEmails' => true,
  ''
]);

```

### References ###

Nested objects are fairly straight forward, but sometimes you need objects from another pool, and you shouldn't trigger creating new ones.  For example, suppose we want all of our users to be in a group, but we don't want to create a new group for every user.  To do this, we use the factory to add a reference.  A reference is really the same thing as an attribute, but the factory resolves it *after* all pools have been initialized.  This allows you to fetch reference objects from other pools, including circular dependencies, without risking infinite loops.

To add a reference to an object in another pool, we'll just use the factory facade wrap the action in a callable:

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

### Hard-coded objects ###

You may want 50 random objects in a pool, but you may also want a few objects that you can reference in tests which are defined explicitly by you, so you know up front what to expect.  Luckily that's easy as well.  If you have defined a pool, you can manually create new instances regardless of whether or not it will be automatically populated with a preset number.  Any manually created instances will just be added to the pool.

This has an important implication though - if you want to create a few explicit objects, you should do that *after* you have defined all of your pools.  Manual creation of an object will trigger initialization of all defined pools.

You can create a specific object by overriding the definition at create time:

```php
<?php

//... todo doc

```

### Example with Persistence ###

## Contributing ##

Contributions are certainly welcome - just please adhere to the [PSR] coding standards.

### Testing ###

Run the tests with ``