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
    use GraphQlTools\Helper\Context;use GraphQlTools\Helper\QueryExecutor;use GraphQlTools\Helper\TypeRegistry; use GraphQlTools\Utility\TypeMap;
    require_once __DIR__ . '/vendor/autoload.php';   

    // Extend this class to implement specific methods
    $typeRegistry = new TypeRegistry(
        // This should be cached for production, usually in build process
        TypeMap::createTypeMapFromDirectory(__DIR__ . '/YOUR_DIRECTORY_WITH_ALL_TYPE_DECLARATIONS')
    );

    $schema = $typeRegistry->toSchema(
        RootQueryType::class, // Your own root query type
        RootMutationType::class, // Your own root mutation type
        [], // Eagerly loaded types
        [], // Array of directives
    );

    $executor = new QueryExecutor();

    $result = $executor->execute(
        $schema,
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
    use GraphQlTools\Helper\TypeRegistry;use GraphQlTools\Utility\TypeMap;
    $typeRegistry = new TypeRegistry(
        TypeMap::createTypeMapFromDirectory(__DIR__ . '/YOUR_DIRECTORY_WITH_ALL_TYPE_DECLARATIONS')
    );

    // This will return the instance of the Root Query Type
    // and if not available, create if for the first time
    $queryType = $typeRegistry->type(RootQueryType::class);
    
    // Therefore the following comparison will return true
    $queryType === $typeRegistry->type(RootQueryType::class); // => true
```

Every Type / Union / Interface / InputType / Enum will be injected automatically with the instance of the TypeRepository.

## Context

The Context Object is passed to all Resolvers and contains contextual values. This is a good place to add current User information for example.

The Context is also used to automatically Inject Services (or classes) into the Deferred Field loading function or the mutation field resolver.
You can simply extend the Context Object to add functionality to it.

```php
class MyCustomContext extends \GraphQlTools\Helper\Context {

    public function __construct(public readonly User $currentUser, private \Psr\Container\ContainerInterface $container) {}

    /**
    * Decouples the Schema and GraphQL from all your business logic.
    *
    * A data loader executor is responsible to load data from a source and perform business logic.
    * You can refer to a data loader by any string. This method is used to return a loading function or instance
    * of ExecutableByDataLoader. This allows you to separate the business logic completely from GraphQL.
    * 
    * @param string $classNameOrLoaderName
    * @return callable|\GraphQlTools\Contract\ExecutableByDataLoader
    * @throws \Psr\Container\ContainerExceptionInterface
    * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function makeInstanceOfDataLoaderExecutor(string $classNameOrLoaderName) : callable|\GraphQlTools\Contract\ExecutableByDataLoader{
        $this->container->get($className);
    }
}
```

In this example, every resolver now gets access to the current user through your context. Additionally, the context resolves dependencies for resolve functions.

## Defining Types

The most important functionality is how to define Types. Compared to the raw implementation of webonix/graphql, SDL is not supported and slightly different classes are required.

Every Type, Interface, Union, ENUM, Scalar only depends on the TypeRepository without any thirdparty dependencies. This is really important, as their only use is to build the schema.
The resolve functions of the Fields then should use your services to get data from your Application. Only they should depend on Services, which are injected or created from the Context.

This ensures fast schema loading and query execution to work correctly. The only Object knowing your Application is the Context.

Type resolution is done automatically if you provide a ClassName using the TypeRepository. If you need nested Types (like nonnull or list of), pass a closure, which will get the TypeRepository as an argument

```php
Field::withName('myFieldName')->ofType(fn(TypeRepository $typeRepository) => new NonNull($typeRepository->type(MyCustomTypeClass::class)))
```

Full example of Type definition:

```php

    use GraphQL\Type\Definition\Type;
    use GraphQlTools\Definition\GraphQlType;
    use GraphQlTools\Definition\Field\Field;
    use GraphQlTools\Helper\TypeRegistry;
    use GraphQlTools\Definition\Field\DeferredField;
    use GraphQlTools\Definition\Field\InputField;
    use GraphQlTools\Helper\Context;
    
    final class AnimalType extends GraphQlType {
        
        protected function description() : string{
            return 'Provide the description of your type.';
        }
        
        protected function fields() : array {
            // To prevent circular types, this is already wrapped in a closure
            // Provide the fields of your type.
            // --
            // You can, and should access the protected `typeRepository` to reference your own types:
            return [
                Field::withName('id')
                    ->ofType(Type::id()),
                
                // Define custom types using the repository
                Field::withName('customType')
                    ->ofType($this->typeRegistry->type(MyCustomTypeClass::class))
                    ->ofSchemaVariant('Only-On-Public'), # Adds metadata to dynamically hide a field
                   
                
                // With custom resolver, the definition here is equal to above.
                Field::withName('sameCustomType')
                    ->ofType(MyCustomTypeClass::class)
                    ->resolvedBy(fn($data, array $arguments) => $data['items']),
                
                // Defer a field, the logic of deferring is abstracted away    
                Field::withName('myField')
                    ->ofType(MyType::class)
                    ->withArguments(
                        
                        // Define named arguments, works for all fields
                        InputField::withName('id')
                            ->ofType(Type::id())
                            ->withDefaultValue('My Value')
                            ->withDescription('My Description')
                       
                        InputField::withName('second')
                            ->ofType(MyType::class),
                    )
                    ->resolvedBy(function ($data, $arguments, Context $context, $resolveInfo) {
                        $context
                            ->dataLoader(MyDataLoaderExecutor::class)
                            // ->loadMany(...$data->tagIds)
                            ->load($data->id)
                    })
                             
            ];
        }
        
        // Optional, define interfaces of this type.
        protected function interfaces() : array{
            return [
                MamelType::class,
                $this->typeRegistry->type(MyType::class),
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

