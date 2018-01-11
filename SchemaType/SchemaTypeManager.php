<?php

namespace UnitedCMS\CoreBundle\SchemaType;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\SchemaType\Factories\SchemaTypeFactoryInterface;

class SchemaTypeManager
{
    /**
     * @var ObjectType|InputObjectType|InterfaceType|UnionType[]
     */
    private $schemaTypes = [];

    /**
     * @var SchemaTypeFactoryInterface[]
     */
    private $schemaTypeFactories = [];

    /**
     * @return ObjectType|InputObjectType|InterfaceType|UnionType[]
     */
    public function getSchemaTypes(): array
    {
        return $this->schemaTypes;
    }

    /**
     * @return SchemaTypeFactoryInterface[]
     */
    public function getSchemaTypeFactories(): array
    {
        return $this->schemaTypeFactories;
    }

    public function hasSchemaType($key): bool
    {
        return array_key_exists($key, $this->schemaTypes);
    }

    /**
     * Returns the named schema type. If schema type was not found all registered factories get asked if they can
     * create the schema. If no schema was found and no schema could be created, an \InvalidArgumentException will be
     * thrown.
     *
     * @param $key
     * @param Domain $domain
     *
     * @return ObjectType|InputObjectType|InterfaceType|UnionType
     */
    public function getSchemaType($key, Domain $domain = null)
    {
        if (!$this->hasSchemaType($key)) {
            foreach($this->schemaTypeFactories as $schemaTypeFactory) {
                if($schemaTypeFactory->supports($key)) {
                    $this->registerSchemaType($schemaTypeFactory->createSchemaType($this, $domain, $key));
                    break;
                }
            }
        }

        if (!$this->hasSchemaType($key)) {
            throw new \InvalidArgumentException("The schema type: '$key' was not found.");
        }

        return $this->schemaTypes[$key];
    }

    /**
     * @param Type $schemaType
     *
     * @return SchemaTypeManager
     */
    public function registerSchemaType(Type $schemaType)
    {
        if (!$schemaType instanceof InputObjectType && !$schemaType instanceof ObjectType && !$schemaType instanceof InterfaceType && !$schemaType instanceof UnionType && !$schemaType instanceof ListOfType) {
            throw new \InvalidArgumentException(
                'Schema type must be of type ' . ObjectType::class . ' or ' . InputObjectType::class . ' or ' . InterfaceType::class . ' or ' . UnionType::class . ' or ' . ListOfType::class
            );
        }

        if (!isset($this->schemaTypes[$schemaType->name])) {
            $this->schemaTypes[$schemaType->name] = $schemaType;
        }

        return $this;
    }

    /**
     * @param SchemaTypeFactoryInterface $schemaTypeFactory
     *
     * @return SchemaTypeManager
     */
    public function registerSchemaTypeFactory(SchemaTypeFactoryInterface $schemaTypeFactory)
    {
        if(!in_array($schemaTypeFactory, $this->schemaTypeFactories)) {
            $this->schemaTypeFactories[] = $schemaTypeFactory;
        }

        return $this;
    }
}