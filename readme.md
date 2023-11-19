# GraphQl Tools

[![Latest Stable Version](https://poser.pugx.org/le0daniel/graphql-tools/v)](//packagist.org/packages/le0daniel/graphql-tools) [![Total Downloads](https://poser.pugx.org/le0daniel/graphql-tools/downloads)](//packagist.org/packages/le0daniel/graphql-tools) [![License](https://poser.pugx.org/le0daniel/graphql-tools/license)](//packagist.org/packages/le0daniel/graphql-tools)

This is a simple opinionated toolkit for writing scalable code first GraphQL applications in PHP.

Main Features

- Custom extensions support (Tracing, Telemetry of resolvers)
- Support for middlewares of resolvers
- Abstraction for schema registration and multi schema supports. This is similar to the visible function, but more
  transparent.
- Abstract classes to extend for defining types / enums / interfaces / unions / scalars
- Fields are built with the easy-to-use field builder
- Simple dataloader implementation to solve N+1 problems
- Code First approach to schema building
- Schema Stitching: Extending of Types with additional fields, allowing you to respect your domain boundires and
  construct the right
  dependency directions in your code.
- Type name aliases, so that you can either use type names of class names of types.

## Installation

Install via composer
```
composer require le0daniel/graphql-tools
```

## Basic Usage

The usage is similar to how you would use `webonyx/graphql` and define object types. Instead of extending the default
classes, you extend the abstract classes provided.
Every resolve function is wrapped by the ProxyResolver class, which provides support for extensions.

At the core, we start with a schema registry. There you register types and register extensions to types.

```php
<?php
    use GraphQlTools\Helper\Registry\SchemaRegistry;
    use GraphQlTools\Contract\TypeRegistry;
    use GraphQlTools\Definition\Field\Field;
    use GraphQlTools\Helper\QueryExecutor;
    use GraphQlTools\Definition\Extending\Extend;
    require_once __DIR__ . '/vendor/autoload.php';   

    $schemaRegistry = new SchemaRegistry();
    
    // You can use a classname for lazy loading or an instance of this type class.
    // You can register object-types, interfaces, enums, input-types, scalars and unions
    // See: Defining Types
    $schemaRegistry->register(Type::class);
    
    // You can extend types and interfaces with additional fields
    // See: Extending Types
    $schemaRegistry->extend(
        Extend::type('Lion')->withFields(fn(TypeRegistry $registry) => [
            Field::withName('species')->ofType($registry->string())
        ])
    );

    $schema = $schemaRegistry->createSchema(
        RootQueryType::class, // Define
        'MutationRoot', // Your own root mutation type,
    );

    $executor = new QueryExecutor();

    $result = $executor->execute(
        $schema,
        ' query { whoami }',
        new Context(),
        [], // variables array
        null, // Root Value
        null, // operation name
    );
```

## Schema Registry

The schema registry serves as a single place where you register all types that exist in your graphql schema.
You can then use the createSchema method to create different variations of your schema based on rules you pass to it.
This is more transparent than using the visible method on your fields, as you can print different variants and see what
different users might be able to see.
Schema rules allow you to granularly show and hide fields. A schema rule file gets an instance of every field a type has
and lets you decide if it is visible or not.

**All types and fields are by default lazy loaded for best performance.**

### Loading all types in a directory

To simplify type registration, you can use the TypeMap utility to load all types in a directory. It uses Glob to find
all PHP files. In production, **you should cache those**.

```php
    use GraphQlTools\Helper\Registry\SchemaRegistry;
    use GraphQlTools\Utility\TypeMap;

    $schemaRegistry = new SchemaRegistry();
    
    [$types, $typeExtensions] = TypeMap::createTypeMapFromDirectory('/your/directory/with/GraphQL');
    
    $schemaRegistry->registerTypes($types);
    $schemaRegistry->extendMany($typeExtensions);
```

## Define Types

An object type can be defined, by creating a class that extends the `GraphQlTools\Definition\GraphQlType` class.
The Type name is automatically deduced by the class name. For this to work, the class name needs to end in Type.
Example: class QueryType => Query (Name in schema).
You can overwrite this behaviour by overwriting the getName function.

```php
use GraphQlTools\Definition\GraphQlType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;

class QueryType extends GraphQlType {

    // Define Fields of the type. Use the type registry to reference all other types in the schema.
    // You MUST use the type registry for referencing all types. The type registry guarantees that the type is only 
    // created once and reused everywhere.
    protected function fields(TypeRegistry $registry) : array {
        return [
            Field::withName('currentUser')
                ->ofType($registry->nonNull($registry->string()))
                ->resolvedBy(fn(array $user) => $user['name']),
            // More fields
        ];
    }
    
    protected function description() : string{
        return 'Entry point into the schema query.';
    }
    
    // Optional
    protected function interfaces(): array {
        return [
            UserInterface::class,
            'Animal', 
        ];
    }
    
    // Optional, define middlewares for the resolver. See Middlewares.
    protected function middleware() : array|null{
        return [];
    }
}
```

## Defining Fields and InputFields

It is required to use the Field and Input fields builder to define Fields (output) and InputFields for input (InputType
fields or Arguments).
This is required for the Framework to work correctly. It attaches ProxyResolver under the hood for extensions and
Middlewares to work correctly.

Additionally, you can define Tags, which can be used to define visibility of fields in different schema variations.
Types need to be referenced via the Type Registry. The sole job of the type registry is to create referenced types only
once in the complete schema.
This is automatically created for you when you create a schema from the schema registry.

By default, the resolver is using the default resolve function from Webonyx (`Executor::getDefaultFieldResolver()`).

Usage (For a type example):

```php

    use GraphQlTools\Definition\GraphQlType;
    use GraphQlTools\Contract\TypeRegistry;
    use GraphQlTools\Definition\Field\Field;
    use GraphQL\Type\Definition\ResolveInfo;
    
    
    final class AnimalType extends GraphQlType {
        
        protected function description() : string{
            return 'Provide the description of your type.';
        }
        
        protected function fields(TypeRegistry $registry) : array {
            
            return [
                // Uses the default resolver
                Field::withName('id') ->ofType($registry->id()),
                
                // Define custom types using the repository
                Field::withName('customType')
                    ->withDescription('This is a custom type')
                    ->ofType($registry->type(CustomType::class))
                    ->tags('public', 'stable'),
                    
                // Using type name instead of class name
                Field::withName('customType2')
                    ->ofType($registry->type('Custom'))
                    ->tags('public', 'stable'),
                   
                // With Resolver
                Field::withName('userName')
                    ->ofType($registry->string())
                    ->resolvedBy(fn(User $user, array $arguments, $context, ResolveInfo $info): string => $user->name),
                
                // Define arguments 
                Field::withName('fieldWithArgs')
                    ->ofType($registry->string())
                    ->withArguments(
                        
                        // Define named arguments, works for all fields
                        InputField::withName('name')
                            ->ofType($registry->nonNull($registry->string()))
                            ->withDefaultValue('Anonymous')
                            ->withDescription('My Description')
                    )
                    ->resolvedBy(function ($data, array $arguments, $context, ResolveInfo $resolveInfo): string {
                        return "Hello {$arguments['name']}";
                    })
                             
            ];
        }
    }
```

## Middleware

A middleware is a function that is executed before and after the real resolve function is called. It follows an onion
principle. You can define multiple middleware functions to prevent data leakage on a complete type or on specific
fields.

Middlewares can be defined for a type (applying to all fields) or to a fields.

**Signature**

```php
use GraphQL\Type\Definition\ResolveInfo;
use Closure;

$middleware = function(mixed $data, array $arguments, $context, ResolveInfo $resolveInfo, Closure $next): mixed {
    // Do something before actually resolving the field.
    if (!$context->isAdmin()) {
        return null;
    }
    
    $result = $next($data, $arguments, $context, $resolveInfo);
    
    // Do something after the field was resolved. You can manipulate the result here.
    if ($result === 'super secret value') {
        return null;
    }
    
    return $result;
} 
```

**Usage**
You can define multiple middleware for a type (Those middlewares are then pretended to all Fields of that type) or only
specific fields.

```php
use GraphQlTools\Definition\Field\Field;

Field::withName('fieldWithMiddleware')
    ->middleware(
        $middleware,
        function(mixed $data, array $arguments, $context, ResolveInfo $resolveInfo, Closure $next) {
            return $next($data, $arguments, $context, $resolveInfo);
        }
    )
```

## Extending Types

Extending types and interfaces allows you to add one or many fields to a type outside from its base declaration. This is
usually the case when you don't want to cross domain boundaries, but need to add additional fields to another type. This
allows you to stitch a schema together.

The simplest way is to use the schema registry and extend an object type or interface, passing a closure that declares
additional fields.

```php
use GraphQlTools\Helper\Registry\SchemaRegistry;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Definition\Extending\Extend;
$schemaRegistry = new SchemaRegistry();

$schemaRegistry->extend(
    Extend::type('Animal')->withFields(fn(TypeRegistry $registry): array => [
            Field::withName('family')
                ->ofType($registry->string())
                ->resolvedBy(fn() => 'Animal Family')
        ]),
);
```

### Using Classes

Our approach allows to use classes, similar to types, that define type extensions.

```php
use GraphQlTools\Definition\Extending\ExtendGraphQlType;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Helper\Registry\SchemaRegistry;

class ExtendsAnimalType extends ExtendGraphQlType {
    public function fields(TypeRegistry $registry) : array {
        return [
            Field::withName('family')
                ->ofType($registry->string())
                ->resolvedBy(fn() => 'Animal Family')
        ];
    }
    
    // Optional
    protected function middleware() : array{
        return [];
    }
    
    // Optional, can be inferred by class name
    // Follow naming pattern: Extends[TypeOrInterfaceName][Type|Interface]
    public function typeName(): string {
        return 'Animal';
    }
}

$schemaRegistry = new SchemaRegistry();

$schemaRegistry->extend(ExtendsAnimalType::class);
// Or with manually given type to extend name
$schemaRegistry->extend(ExtendsAnimalType::class, 'Animal');
// OR
$schemaRegistry->extend(new ExtendsAnimalType());
```

### Federation Middleware

To decouple and remove references to the complete data object in the resolver, a Federation middleware is provided.

2 Middlewares are provided:

1. `Federation::key('id')`, extracts the id property of the data object/array
2. `Federation::field('id')`, runs the resolver of the field id

```php
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Utility\Middleware\Federation;
use GraphQlTools\Contract\TypeRegistry;

/** @var TypeRegistry $registry */
Field::withName('extendedField')
    ->ofType($registry->string())
    ->middleware(Federation::key('id'))
    ->resolvedBy(fn(string $animalId) => "Only the ID is received, not the complete Animal Data.");
```

## Query execution (QueryExecutor)

The query executor is used to execute a query. It attaches Extensions and Validation Rules, handles error mapping and
logging.

**Extensions**: Classes that can drop in to the execution of the query and listen to events. They do not change the
result of the query but can collect valuable telemetry data. They are contextual and a new instance is created for each
query that is executed.
**Validation Rules**: They validate the query before it is executed. They are not necessarily contextual. If a factory
or classname is provided, a new instance is created for each query that is executed.
**Error Mapper**: Receives an instance of Throwable and the corresponding GraphQl Error. It is tasked to map this to a
Throwable that potentially implements ClientAware. This allows you to disconnect your internal exceptions from
exceptions that you use in GraphQL.
**Error Logger**: Receives an instance of Throwable before it is mapped. This allows you to log errors that occurred.

### Extensions and ValidationRules

If you define a factory, it will get the context as argument. This allows you to dynamically create or attach them based
on the user that is executing your query.
If an extension of validation rule implements `GraphQlTools\Contract\ProvidesResultExtension`, you can add data to the
extensions array of the result, according to the graphql spec.

```php
use GraphQlTools\Helper\QueryExecutor;
use GraphQlTools\Helper\Validation\QueryComplexityWithExtension;
use GraphQlTools\Helper\Validation\CollectDeprecatedFieldNotices;

$executor = new QueryExecutor(
    [fn($context) => new YourTelemetryExtension],
    [CollectDeprecatedFieldNotices::class, fn($context) => new QueryComplexityWithExtension($context->maxAllowedComplexity)],
    function(Throwable $throwable, \GraphQL\Error\Error $error): void {
        YourLogger::error($throwable);
    },
    function(Throwable $throwable): Throwable {
        match ($throwable::class) {
            AuthenticationError::class => GraphQlErrorWhichIsClientAware($throwable),
            default => $throwable
        }
    }
);

$result = $executor->execute(/* ... */);

// You can access extensions after the execution
$telemetryExtension = $result->getExtension(YourTelemetryExtension::class);
$application->storeTrace($telemetryExtension->getTrace());

// You can access validation rules after execution
$deprecationNotices = $result->getValidationRule(CollectDeprecatedFieldNotices::class);
$application->logDeprecatedUsages($deprecationNotices->getMessages());

$jsonResult = json_encode($result);
```

## ValidationRules

We use the default validation rules from webonyx/graphql with an additional rule to collect deprecation notices.

You can define custom validation rules, by extending the default ValidationRule class from webonyx/graphql. If you
additionally implement the ProvidesResultExtension, rules can add an entry to the extensions field in the result.
Validation rules are executed before the query is actually run.

## Extensions

Extensions are able to hook into the execution and collect data during execution. They allow you to collect traces or
telemetry data. Extensions are not allowed to manipulate results of a field resolver. If you want to manipulate results,
you need to use middlewares.
Extensions are contextual and a new instance is created each time a query is run. You can define extensions by passing a
classname to the query executor or a factory. To each factory, the current Context is passed in.

To define an extension, a class needs to implement the ExecutionExtension interface. Extensions can additionally
implement ProvidesResultExtension and add entries to the extensions field in the result.
The abstract class Extension implements some helper and general logic to build extensions easily.

Following events are provided:

- StartEvent: when the execution is started, but no code has been run yet
- ParsedEvent: once the query is successfully parsed
- EndEvent: once the execution is done

Each event contains specific properties and the time of the event in nanoseconds.

During execution, extensions can hook into the resolve function of each resolver using the visitField hook. You can pass
a closure back, which is executed once resolution is done. This is then executed after all promises are resolved, giving
you access to the actual data resolved for a field. In case of a failure, a throwable is returned.

```php
use GraphQlTools\Helper\Extension\Extension;

class MyCustomExtension extends Extension {
    //...
    public function visitField(\GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent $event) : ?Closure{
        Log::debug('Will resolve field', ['name' => $event->info->fieldName, 'typeData' => $event->typeData, 'args' => $event->arguments]);
        return fn($resolvedValue) => Log::debug('did resolve field value to', ['value' => $resolvedValue]); 
    }
}
```