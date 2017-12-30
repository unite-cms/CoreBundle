<?php

namespace UnitedCMS\CoreBundle\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

abstract class DatabaseAwareTestCase extends ContainerAwareTestCase
{

    /**
     * @var EntityManager
     */
    protected $em;

    public function setUp()
    {
        parent::setUp();
        $this->em = $this->container->get('doctrine.orm.default_entity_manager');
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown()
    {

        parent::tearDown();
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
    }
}