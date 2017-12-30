<?php

namespace UnitedCMS\CoreBundle\Collection;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CollectionTypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has('united.cms.collection_type_manager')) {
            return;
        }

        $definition = $container->findDefinition('united.cms.collection_type_manager');
        $taggedServices = $container->findTaggedServiceIds('united_cms.collection_type');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('registerCollectionType', array(new Reference($id)));
        }
    }
}