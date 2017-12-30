<?php

namespace UnitedCMS\CoreBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use UnitedCMS\CoreBundle\Collection\CollectionTypeCompilerPass;
use UnitedCMS\CoreBundle\Field\FieldTypeCompilerPass;
use UnitedCMS\CoreBundle\Service\AlterDoctrineExtensionDefinitionsCompilerPass;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeCompilerPass;

class UnitedCMSCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new FieldTypeCompilerPass());
        $container->addCompilerPass(new CollectionTypeCompilerPass());
        $container->addCompilerPass(new AlterDoctrineExtensionDefinitionsCompilerPass());
        $container->addCompilerPass(new SchemaTypeCompilerPass());
    }
}
