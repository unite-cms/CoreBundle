<?php

namespace UniteCMS\CoreBundle\DependencyInjection;

use UniteCMS\CoreBundle\GraphQL\SchemaManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SchemaManagerCompilerPass implements CompilerPassInterface
{

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has(SchemaManager::class)) {
            return;
        }

        $definition = $container->findDefinition(SchemaManager::class);


        // Register schema provider
        $taggedServices = $container->findTaggedServiceIds('unite.graphql.schema_provider');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('registerProvider', [new Reference($id)]);
        }


        // Register schema extender
        $taggedServices = $container->findTaggedServiceIds('unite.graphql.schema_extender');

        foreach ($taggedServices as $id => $tags) {
            foreach($tags as $tag) {
                $definition->addMethodCall('registerExtender', [
                    new Reference($id),
                    $tag['position'] ?? null
                ]);
            }
        }

        // Register schema modifier
        $taggedServices = $container->findTaggedServiceIds('unite.graphql.schema_modifier');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('registerModifier', [new Reference($id)]);
        }

        // Register field resolver
        $taggedServices = $container->findTaggedServiceIds('unite.graphql.field_resolver');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('registerFieldResolver', [new Reference($id)]);
        }

        // Register type resolver
        $taggedServices = $container->findTaggedServiceIds('unite.graphql.type_resolver');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('registerTypeResolver', [new Reference($id)]);
        }

        // Register scalar resolver
        $taggedServices = $container->findTaggedServiceIds('unite.graphql.scalar_resolver');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('registerScalarResolver', [new Reference($id)]);
        }
    }
}
