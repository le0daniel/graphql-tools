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
- Lazy by default for best performance

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

**The schema registery is the central entity that contains all type definitions for a graphql schema.**

You register types, extend types and then create a schema variation. Internally, a TypeRegistry is given to all types
and fields, allowing you to seamlessly refer to other types in the schema.
The type registry solves the problem that each type class is only instanced once per schema and lazily for best
performance.

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

### Aliases

In GraphQL, all types have a name. In a code first approach as described here, you define a class, which then creates a
name. So in code, you have both the classname and the type name which is used in GraphQL.
We automatically create aliases for the classnames. This allows you to reference other types either by their class name
or the type name which is used in GraphQL.

Class names can be better to work with inside a module, as you can now statically analyse the class usages.

```php
    use GraphQlTools\Helper\Registry\SchemaRegistry;
    use GraphQlTools\Utility\TypeMap;

    $schemaRegistry = new SchemaRegistry();
    $schemaRegistry->register(SomeName\Space\MyType::class);

    // You can now use both, the class name or the type name as in GraphQL.
    $registry->type(SomeName\Space\MyType::class) === $registry->type('My');
```

### Creating a schema from the registry

The schema registries task is to create a schema from all registered types dynamically.
You can hide and show schema variants by using schema rules. Rules are mostly based on tags.
In contrast to using the visibility function, this is more transparent. You can print different
Schema variants and use tools to verify the schema or breaking changes. A hidden field can not be
queried by any user. This prevents data leakage.

**Provided Rules**:

- AllVisibleSchemaRule (default): All fields are visible
- TagBasedSchemaRules: Black- and whitelist based on field tags.

You can define your own rules by implementing the SchemaRules interface.

```php
    use GraphQlTools\Helper\Registry\SchemaRegistry;
    use GraphQlTools\Helper\Registry\TagBasedSchemaRules;
    use GraphQlTools\Contract\SchemaRules;
    
    $schemaRegistry = new SchemaRegistry();
    // Register all kind of types
    $publicSchema = $schemaRegistry->createSchema(
        queryTypeName: 'Query',
        schemaRules: new TagBasedSchemaRules(ignoreWithTags: 'unstable', onlyWithTags: 'public')
    );
    
    $publicSchemaWithUnstableFields = $schemaRegistry->createSchema(
        queryTypeName: 'Query',
        schemaRules: new TagBasedSchemaRules(onlyWithTags: 'public')
    );

    class MyCustomRule implements SchemaRules {
        
        public function isVisible(Field|InputField|EnumValue $item): bool {
            // Determine if a field is visible or not.
            return Arr::contains('my-tag', $item->getTags());
        }
    }
```

## Define Types

In a code first approach to graphql, each type is represented by a class in code.

Naming conventions and classes to extend:

| Type           | ClassName         | Extends            | Example                                               |
|----------------|-------------------|--------------------|-------------------------------------------------------|
| Object Type    | `[Name]Type`      | `GraphQlType`      | AnimalType => type Animal                             |
| Input Type     | `[Name]InputType` | `GraphQlInputType` | CreateAnimalInputType => input type CreateAnimalInput |
| Interface Type | `[Name]Interface` | `GraphQlInterface` | MammalInterface => interface Mammal                   |
| Union Type     | `[Name]Union`     | `GraphQlUnion`     | SearchResultUnion => union SearchResult               |
| Scalar Type    | `[Name]Scalar`    | `GraphQlScalar`    | ByteScalar => scalar Byte                             |
| Directive Type | `[Name]Directive` | `GraphQlDirective` | ExportVariablesDirective => directive ExportVariables |

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

In a code first approach, the field definition and the resolve function live in the same place. The field builders allow
you to easily
construct fields with all required and possible attributes, combined with their resolvers, all in code directly.

Field builders are immutable, and thus flexible to use and reuse.

It attaches a ProxyResolver class to decorate your resolve function under the hood for extensions and Middlewares to
work correctly.

To declare types and reference other types, a Type Registry is given to each instance where fields are defined.
This allows you to reference other types that exist in your schema. The type registry itself takes care of lazy loading
and ensures that every only one instance of a type is created in a schema.

Additionally, you can define Tags, which can be used to define visibility of fields in different schema variations.
This is automatically created for you when you create a schema from the schema registry.

By default, the resolver is using the default resolve function from Webonyx (`Executor::getDefaultFieldResolver()`).

In its simplest form:

```php
use GraphQlTools\Definition\Field\Field;
/** @var \GraphQlTools\Contract\TypeRegistry $registry */

// In type Animal
Field::withName('myField')->ofType($registry->nonNull($registry->id()));
Field::withName('myOtherField')->ofType($registry->listOf($registry->type('Other')));

// Results in GraphQL
// type Animal {
//   myField: String!
//   myOtherField: [Other]
// }
```

Broader Usage:

```php

    use GraphQlTools\Definition\GraphQlType;
    use GraphQlTools\Contract\TypeRegistry;
    use GraphQlTools\Definition\Field\Field;
    use GraphQL\Type\Definition\ResolveInfo;
    
    
    final class AnimalType extends GraphQlType {
        // ...        
        protected function fields(TypeRegistry $registry) : array {
            
            return [
                // Uses the default resolver
                Field::withName('id')->ofType($registry->id()),
                
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

### Reuse Fields

Creating reusable fields are easy. Create a function or method that returns a field, then it can be used everywhere.

```php
use GraphQlTools\Definition\Field\Field;
use GraphQlTools\Contract\TypeRegistry;
use GraphQlTools\Definition\GraphQlType;

class ReusableFields {
    public static function id(TypeRegistry $registry): Field {
        return Field::withName('id')
            ->ofType($registry->nonNull($registry->id()))
            ->withDescription('Globally unique identifier.')
            ->resolvedBy(fn(Identifiable $data): string => $data->globallyUniqueId());
    }
}

// Usage in type:
class MyType extends GraphQlType {
    protected function fields(TypeRegistry $registry) : array {
        return [
            ReusableFields::id($registry)->withDescription('Overwritten description...'),
            
            // Other Fields
            Field::withName('other')->ofType($registry->string()),
        ];
    }
}

```

### Cost

In GraphQL, a query can quickly be really expensive to compute, as you can navigate through deep relationships.
To prevent users exceeding certain limits, you can limit the complexity a query can have.

Webonyx provides a way to calculate complexity ahead of executing the query. This is done via the MaxComplexityRule. For
it to work, each field needs to define a complexity function.

We use the concept of cost, where each field defines its own cost statically and provide a helper to compute variable
complexity based on arguments.

```
query {
    # Cost: 2
    animals(first: 5) {
        id # Cost: 0
        name # Cost: 1
        # Cost 2
        relatedAnimals(first: 5) {
            id # Cost: 0
            name # Cost: 1
        }
    }
}
```

In this example we see both components:

- Static costs per field (animals: 2, id: 0, name: 1, relatedAnimals: 2, id: 0, name: 1)
- Variable costs: first: 5

As in the worst case, all 5 entities are loaded from a DataBase, this needs to be taken into account when determining
the max cost of the query.
Example:

- relatedAnimals cost at max: 5 * (Cost of Animal: 1) + (relatedAnimals price: 2) = 7
- animals cost at max: 5 * (Cost of Animal: 1 + Max(relatedAnimals): 7) + (animals price: 2) = 42

To represent such dynamic costs, you can pass a closure as a second parameter. The return of it will be used to multiply
the cost of all children by.

```php
$field->cost(2, fn(array $args): int => $args['first'] ?? 15);
```

**Note:** Cost is used to determine the worst case cost of a query. If you want to collect the actual cost, use the
ActualCostExtension. It hooks into resolvers and aggregates the cost of fields that were actually present in the query.

## Middleware

A middleware is a function that is executed before and after the real resolve function is called. It follows an onion
principle. Outer middlewares are called first and invoke the level deeper. You can define multiple middleware functions
to prevent data leakage on a complete type or on specific fields.

Middlewares can be defined for a type (applying to all fields) or to a fields. You need to call `$next(...)` to invoke
the next layer.

Middlewares allow you to manipulate data that is passed down to the actual field resolver and then the actual result of
the resolver.
For example, you can use a middleware to validate arguments before the actual resolve function is called.

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

ClassName naming patterns for lazy registration to work correctly: `Extends[TypeOrInterfaceName](Type|Interface)`
Examples: 
- ExtendsQueryType => Extends the type with the name Query
- ExtendsUserInterface => Extends the interface with the name User

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
// If the class does not follow the naming patterns, you need to use the extended type name
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
use GraphQlTools\Utility\Time;
use GraphQlTools\Data\ValueObjects\Events\VisitFieldEvent;

class MyCustomExtension extends Extension {
    //...
    public function visitField(VisitFieldEvent $event) : ?Closure{
        Log::debug('Will resolve field', ['name' => $event->info->fieldName, 'typeData' => $event->typeData, 'args' => $event->arguments]);
        return fn($resolvedValue) => Log::debug('did resolve field value to', [
            'value' => $resolvedValue, 
            'durationNs' => Time::durationNs($event->eventTimeInNanoSeconds),
        ]); 
    }
}
```