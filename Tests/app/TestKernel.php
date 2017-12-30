<?php

namespace Tests\app;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class TestKernel extends Kernel
{
  public function registerBundles()
  {
    $bundles = [

      // Put the core bundle at position 1 to allow service tag priority altering.
      // @see https://github.com/symfony/symfony/issues/15256
      new \UnitedCMS\CoreBundle\UnitedCMSCoreBundle(),

      new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
      new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
      new \Symfony\Bundle\TwigBundle\TwigBundle(),
      new \Symfony\Bundle\MonologBundle\MonologBundle(),
      new \Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
      new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
      new \Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

      new \JMS\SerializerBundle\JMSSerializerBundle(),
      new \Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
      new \Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),

      new \Symfony\Bundle\DebugBundle\DebugBundle()
    ];

    return $bundles;
  }

  public function getRootDir()
  {
    return __DIR__;
  }

  public function getCacheDir()
  {
    return dirname(__DIR__).'/app/var/cache/'.$this->getEnvironment();
  }

  public function getLogDir()
  {
    return dirname(__DIR__).'/app/var/logs';
  }

  public function registerContainerConfiguration(LoaderInterface $loader)
  {
    $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
  }
}

class_alias('Tests\app\TestKernel', 'TestKernel');

