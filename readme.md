# GraphQl Tools
[![Latest Stable Version](https://poser.pugx.org/le0daniel/graphql-tools/v)](//packagist.org/packages/le0daniel/graphql-tools) [![Total Downloads](https://poser.pugx.org/le0daniel/graphql-tools/downloads)](//packagist.org/packages/le0daniel/graphql-tools) [![Latest Unstable Version](https://poser.pugx.org/le0daniel/graphql-tools/v/unstable)](//packagist.org/packages/le0daniel/graphql-tools) [![License](https://poser.pugx.org/le0daniel/graphql-tools/license)](//packagist.org/packages/le0daniel/graphql-tools)

This is a simple opinionated toolkit for writing GraphQL applications in PHP. It supports extensions and in particulat the apollo tracing format.

Main Features

 - (not working yet) Apollo Tracing support
 - Custom extensions support
 - Default TypeRepository Implementation with lazy loading
 - Strict Type Checks
 - Abstract classes to extend for defining types / enums / interfaces / unions / scalars / Fields
 - Fields are built with the easy to use Field builder
 - Abstraction of deferred fields to solve the N+1 problem
 - Code First approach to schema building

## Basic Usage

The usage is similar to how you would use `webonyx/graphql` and define object types, but it comes with default implementations for all GraphQL types.

Every resolve function is wrapped by the ProxyResolver class, which enables the extensions to work and is extensible with different hooks. 

Additionally, default implementations for the Context and the type repository are provided. Those can be extended to fit your needs or usage with a specific Framework.

Everything begins by defining a new Type Repository. The Type Repository makes sure that all types are only created once.

```php
<?php
    use GraphQlTools\Context;use GraphQlTools\Helper\QueryExecutor;use GraphQlTools\TypeRepository;
    require_once __DIR__ . '/vendor/autoload.php';   

    // Extend this class to implement specific methods
    $typeRepository = new TypeRepository(
        // This should be cached for production, usually in build process
        TypeRepository::createTypeMapFromDirectory(__DIR__ . '/YOUR_DIRECTORY_WITH_ALL_TYPE_DECLARATIONS')
    );

    $executor = new QueryExecutor(
        $typeRepository->toSchema(
            RootQueryType::class, // Your own root query type
            RootMutationType::class, // Your own root mutation type
            [], // Eagerly loaded types
            [], // Array of directives
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
    $typeRepository = new TypeRepository(
        TypeRepository::createTypeMapFromDirectory(__DIR__ . '/YOUR_DIRECTORY_WITH_ALL_TYPE_DECLARATIONS')
    );

    // This will return the instance of the Root Query Type
    // and if not available, create if for the first time
    $query = $typeRepository->type(RootQueryType::class);
    
    // Therefore the following comparison will return true
    $query === $typeRepository->type(RootQueryType::class); // => true
```

Every Type / Union / Interface / InputType / Enum will be injected automatically with the instance of the TypeRepository.

## Defining Types

The most important functionality is how to define Types. Compared to the raw implementation of webonix/graphql, SDL is not supported and slightly different classes are required.

All of your types must extend our implementation of the webonix/graphql types. This is required, as those ensure that the ProxyResolver is attached successfully. The proxy resolver in term makes sure that extensions work.

```php

    use GraphQL\Type\Definition\Type;
    use GraphQlTools\Definition\GraphQlType;
    use GraphQlTools\Definition\Field\Field;
    use GraphQlTools\TypeRepository;
    use GraphQlTools\Definition\Field\DeferredField;
    use GraphQlTools\Definition\Field\Argument;
    
    final class AnimalType extends GraphQlType {
        
        protected function description() : string{
            return 'Provide the description of your type.';
        }
        
        protected function fields() : array{
            // To prevent circular types, this is already wrapped in a closure
            // Provide the fields of your type.
            // --
            // You can, and should access the protected `typeRepository` to reference your own types:
            return [
                Field::withName('id')
                    ->ofType(Type::id()),
                
                // Define custom types using the repository
                Field::withName('customType')
                    ->ofType(fn(TypeRepository $typeRepository) => $typeRepository->type(MyCustomTypeClass::class)),
                
                // With custom resolver
                Field::withName('sameCustomType')
                    ->ofType(MyCustomType::class)
                    ->resolvedBy(fn($data, array $arguments, Context $context, ResolveInfo $resolveInfo) => $data['items']),
                
                // Defer a field, the logic of deferring is abstracted away    
                DeferredField::withName('myField')
                    ->ofType(MyType::class)
                    ->withArguments(
                        
                        // Define named arguments, works for all fields
                        Argument::withName('id')
                            ->ofType(Type::id())
                            ->withDefaultValue('My Value')
                            ->withDescription('My Description')
                            ->withValidator(fn(string $value, array $allArguments) => $value or throw new Error()),
                       
                        Argument::withName('second')
                            ->ofType(MyType::class),
                    )
                    ->resolveAggregated(static function(array $aggregatedData, array $arguments, Context $context){
                        return yourDataLoadingFunction(array_column($aggregatedData, 'id'));
                    })
                    ->resolveItem(static function(array $data, $loadedData){
                        return $loadedData[$data['id']];
                    })             
            ];
        }
        
        // Optional, define interfaces of this type.
        protected function interfaces() : array{
            return [
                MamelType::class,
                fn(TypeRepository $typeRepository) => $typeRepository->type(MyType::class),
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
        
        protected function metadata() : mixed{
            return [
                'My Custom metadata for introspection'
            ]       
        }
    }
```

