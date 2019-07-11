<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace Hyperf\GraphQL;

use Psr\Container\ContainerInterface;
use TheCodingMachine\GraphQLite\Mappers\RecursiveTypeMapperInterface;
use TheCodingMachine\GraphQLite\MissingAnnotationException;
use TheCodingMachine\GraphQLite\NamingStrategyInterface;
use TheCodingMachine\GraphQLite\TypeRegistry;
use TheCodingMachine\GraphQLite\Types\MutableObjectType;

/**
 * This class is in charge of creating Webonyx GraphQL types from annotated objects that do not extend the
 * Webonyx ObjectType class.
 */
class TypeGenerator
{
    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @var FieldsBuilderFactory
     */
    private $fieldsBuilderFactory;

    /**
     * @var NamingStrategyInterface
     */
    private $namingStrategy;

    /**
     * @var TypeRegistry
     */
    private $typeRegistry;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        AnnotationReader $annotationReader,
        FieldsBuilderFactory $fieldsBuilderFactory,
        NamingStrategyInterface $namingStrategy,
        TypeRegistry $typeRegistry,
        ContainerInterface $container
    ) {
        $this->annotationReader = $annotationReader;
        $this->fieldsBuilderFactory = $fieldsBuilderFactory;
        $this->namingStrategy = $namingStrategy;
        $this->typeRegistry = $typeRegistry;
        $this->container = $container;
    }

    /**
     * @param string $annotatedObjectClassName the FQCN of an object with a Type annotation
     * @param RecursiveTypeMapperInterface $recursiveTypeMapper
     * @throws \ReflectionException
     * @return MutableObjectType
     */
    public function mapAnnotatedObject(string $annotatedObjectClassName, RecursiveTypeMapperInterface $recursiveTypeMapper): MutableObjectType
    {
        $refTypeClass = new \ReflectionClass($annotatedObjectClassName);

        $typeField = $this->annotationReader->getTypeAnnotation($refTypeClass);

        if ($typeField === null) {
            throw MissingAnnotationException::missingTypeException();
        }

        $typeName = $this->namingStrategy->getOutputTypeName($refTypeClass->getName(), $typeField);

        if ($this->typeRegistry->hasType($typeName)) {
            return $this->typeRegistry->getMutableObjectType($typeName);
        }

        if (! $typeField->isSelfType()) {
            $annotatedObject = $this->container->get($annotatedObjectClassName);
        } else {
            $annotatedObject = null;
        }

        return TypeAnnotatedObjectType::createFromAnnotatedClass($typeName, $typeField->getClass(), $annotatedObject, $this->fieldsBuilderFactory, $recursiveTypeMapper);
        /*return new ObjectType([
            'name' => $typeName,
            'fields' => function() use ($annotatedObject, $recursiveTypeMapper, $typeField) {
                $parentClass = get_parent_class($typeField->getClass());
                $parentType = null;
                if ($parentClass !== false) {
                    if ($recursiveTypeMapper->canMapClassToType($parentClass)) {
                        $parentType = $recursiveTypeMapper->mapClassToType($parentClass, null);
                    }
                }

                $fieldProvider = $this->controllerQueryProviderFactory->buildFieldsBuilder($recursiveTypeMapper);
                $fields = $fieldProvider->getFields($annotatedObject);
                if ($parentType !== null) {
                    $fields = $parentType->getFields() + $fields;
                }
                return $fields;
            },
            'interfaces' => function() use ($typeField, $recursiveTypeMapper) {
                return $recursiveTypeMapper->findInterfaces($typeField->getClass());
            }
        ]);*/
    }

    /**
     * @param object $annotatedObject an object with a ExtendType annotation
     * @param MutableObjectType $type
     * @param RecursiveTypeMapperInterface $recursiveTypeMapper
     */
    public function extendAnnotatedObject($annotatedObject, MutableObjectType $type, RecursiveTypeMapperInterface $recursiveTypeMapper)
    {
        $refTypeClass = new \ReflectionClass($annotatedObject);

        $extendTypeAnnotation = $this->annotationReader->getExtendTypeAnnotation($refTypeClass);

        if ($extendTypeAnnotation === null) {
            throw MissingAnnotationException::missingExtendTypeException();
        }

        //$typeName = $this->namingStrategy->getOutputTypeName($refTypeClass->getName(), $extendTypeAnnotation);
        $typeName = $type->name;

        /*if ($this->typeRegistry->hasType($typeName)) {
            throw new GraphQLException(sprintf('Tried to extend GraphQL type "%s" that is already stored in the type registry.', $typeName));
        }

        if (!$type instanceof MutableObjectType) {
            throw new \RuntimeException('TEMP EXCEPTION');
        }*/

        $type->addFields(function () use ($annotatedObject, $recursiveTypeMapper) {
            /*$parentClass = get_parent_class($extendTypeAnnotation->getClass());
            $parentType = null;
            if ($parentClass !== false) {
                if ($recursiveTypeMapper->canMapClassToType($parentClass)) {
                    $parentType = $recursiveTypeMapper->mapClassToType($parentClass, null);
                }
            }*/

            $fieldProvider = $this->fieldsBuilderFactory->buildFieldsBuilder($recursiveTypeMapper);
            return $fieldProvider->getFields($annotatedObject);
            /*if ($parentType !== null) {
                $fields = $parentType->getFields() + $fields;
            }*/
        });

//        return new ObjectType([
//            'name' => $typeName,
//            'fields' => function() use ($annotatedObject, $recursiveTypeMapper, $type) {
//                /*$parentClass = get_parent_class($extendTypeAnnotation->getClass());
//                $parentType = null;
//                if ($parentClass !== false) {
//                    if ($recursiveTypeMapper->canMapClassToType($parentClass)) {
//                        $parentType = $recursiveTypeMapper->mapClassToType($parentClass, null);
//                    }
//                }*/
//
//                $fieldProvider = $this->fieldsBuilderFactory->buildFieldsBuilder($recursiveTypeMapper);
//                $fields = $fieldProvider->getFields($annotatedObject);
//                /*if ($parentType !== null) {
//                    $fields = $parentType->getFields() + $fields;
//                }*/
//
//                $fields = $type->getFields() + $fields;
//
//                return $fields;
//            },
//            'interfaces' => function() use ($type) {
//                return $type->getInterfaces();
//            }
//        ]);
    }
}