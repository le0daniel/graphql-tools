# GraphQl Tools
[![Latest Stable Version](https://poser.pugx.org/le0daniel/graphql-tools/v)](//packagist.org/packages/le0daniel/graphql-tools) [![Total Downloads](https://poser.pugx.org/le0daniel/graphql-tools/downloads)](//packagist.org/packages/le0daniel/graphql-tools) [![Latest Unstable Version](https://poser.pugx.org/le0daniel/graphql-tools/v/unstable)](//packagist.org/packages/le0daniel/graphql-tools) [![License](https://poser.pugx.org/le0daniel/graphql-tools/license)](//packagist.org/packages/le0daniel/graphql-tools)

This is a simple opinionated toolkit for writing GraphQL applications in PHP. It supports extensions and in particulat the apollo tracing format.

Main Features

 - Apollo Tracing support
 - Custom extensions support
 - Default TypeRepository Implementation (& Lazy Loading)
 - Strict Type Checks
 - Abstract classes to extend for defining types / enums / interfaces / unions / scalars
 - Field definition is still as flexible as webonyx/graphql
 - Extendable ProxyResolver class which allows for reusable validation / authorization

## Basic Usage

The usage is similar to how you would use `webonyx/graphql` and define object types, but it comes with default implementations for all GraphQL types.

Every resolve function is wrapped by the ProxyResolver class, which enables the extensions to work and is extensible with different hooks. 

Additionally, default implementations for the Context and the type repository are provided. Those can be extended to fit your needs or usage with a specific Framework.

Everything begins by defining a new Type Repository. The Type Repository makes sure that all types are only created once.

```php
<?php
    use GraphQlTools\Context;use GraphQlTools\Execution\QueryExecutor;use GraphQlTools\TypeRepository;
    require_once __DIR__ . '/vendor/autoload.php';   

    // Extend this class to implement specific methods
    $typeRepository = new TypeRepository();

    $executor = new QueryExecutor(
        $typeRepository->toSchema(
            RootQueryType::class, // Your own root query type
            RootMutationType::class, // Your own root mutation type
            [] // Array of directives
        )
    );

    $result = $executor->execute(
        ' query { whoami }',
        new Context(),
        [], // variables array
        null, // Root Value
        'query' // operation name
    );
```

## Type Repository

When using GraphQl with Objects definition, it is important to keep in mind that a type can only exist once in a schema.
Meaning, if both of your types `Tiger` and `Lion` have a field named `parent` of type `Animal`, the object defining `Animal` must only be initialized once.

The Type Repository's job is to ensure that types are only created once.

When defining fields with custom types, you must use the TypeRepository.

```php
    use GraphQlTools\TypeRepository;
    $typeRepository = new TypeRepository();

    // This will return the instance of the Root Query Type
    // and if not available, create if for the first time
    $query = $typeRepository->type(RootQueryType::class);
    
    // Therefore the following comparison will return true
    $query === $typeRepository->type(RootQueryType::class); // => true
```

The default implementation (TypeRepository) expects a classname to be provided. This is in most cases enough, but if you have a large schema, you may want to extend this class and overwrite `type` method and provide a custom typeLoader function to use LazyLoading. 
Out of the box we provide a `LazyRepository` implementation which resolves either a TypeName or a ClassName to the correct type. 

```php
    use GraphQlTools\LazyRepository;
    $typeRepository = new LazyRepository([
        RootQueryType::typeName() => RootQueryType::class,
    ]);
```

Default helpers are provided for getting list of types

```php
    use GraphQL\Type\Definition\NonNull;
    use GraphQlTools\TypeRepository;
    
    $typeRepository = new TypeRepository();
    
    $typeRepository->listOfType(AnimalType::class); // graphql => [Animal]
    $typeRepository->listOfType(AnimalType::class, false); // graphql => [Animal!]
    new NonNull($typeRepository->listOfType(AnimalType::class, false)); // graphql => [Animal!]!
```

Every Type / Union / Interface / InputType / Enum will be injected automatically with the instance of the TypeRepository.

## Defining Types

The most important functionality is how to define Types. Compared to the raw implementation of webonix/graphql, SDL is not supported and slightly different classes are required.

All of your types must extend our implementation of the webonix/graphql types. This is required, as those ensure that the ProxyResolver is attached successfully. The proxy resolver in term makes sure that extensions work.

```php

    use GraphQL\Type\Definition\Type;
    use GraphQlTools\Definition\GraphQlType;
    
    final class AnimalType extends GraphQlType {
        
        protected function description() : string{
            return 'Provide the description of your type.';
        }
        
        protected function fields() : array{
            // To prevent circular types, this is already wrapped in a closure
            // Provide the fields of your type.
            // See possibilities: https://webonyx.github.io/graphql-php/type-definitions/object-types/
            // --
            // You can, and should access the protected `typeRepository` to reference your own types:
            return [
                'id' => Type::id(),
                'name' => [
                    'type' => Type::string(),
                    'resolve' => fn() => 'Awesome name', // see chapter Resolve for more details
                ],
                'parent' => [
                    'type' => $this->typeRepository->type(AnimalType::class)
                ]               
            ];
        }
        
        // Optional, define interfaces of this type.
        protected function interfaces() : array{
            return [
                $this->typeRepository->type(MamelType::class),
            ];
        }
        
        // Optional, return the type name, used in your schema.
        // By default, the class basename is used, by removing Type from it
        // Ex: Namespace\AnimalType => Animal
        // 
        // This applies to all different Types (Union, Interface, Type, InputType, Enum)
        // with slightly different endings being removed.
        public static function typeName(): string {
            return 'Animal';
        }  
    }
```

