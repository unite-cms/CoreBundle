<?php

namespace src\UnitedCMS\CoreBundle\Tests\Security;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UnitedCMS\CoreBundle\Entity\ApiClient;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Security\ContentVoter;
use UnitedCMS\CoreBundle\Tests\SecurityVoterTestCase;

class ContentVoterApiClientTest extends SecurityVoterTestCase
{

    /**
     * @var Domain
     */
    protected $domain1;

    /**
     * @var Domain
     */
    protected $domain2;

    /**
     * @var Content
     */
    protected $content1;

    /**
     * @var Content
     */
    protected $content2;

    /**
     * @var ContentType
     */
    protected $contentType1;

    /**
     * @var ContentType
     */
    protected $contentType2;

    public function setUp()
    {
        parent::setUp();

        $this->domain1 = new Domain();
        $this->domain1->setIdentifier('d1')->setId(1);
        $this->domain1->setOrganization($this->org1);

        $this->domain2 = new Domain();
        $this->domain2->setIdentifier('d2')->setId(2);
        $this->domain2->setOrganization($this->org2);

        $this->contentType1 = new ContentType();
        $this->contentType1->setDomain($this->domain1);
        $p1 = $this->contentType1->getPermissions();
        $p1[ContentVoter::UPDATE] = [Domain::ROLE_ADMINISTRATOR];
        $p1[ContentVoter::DELETE] = [Domain::ROLE_ADMINISTRATOR];
        $this->contentType1->setPermissions($p1);

        $this->contentType2 = new ContentType();
        $this->contentType2->setDomain($this->domain2);

        $this->content1 = new Content();
        $this->content1->setContentType($this->contentType1);

        $this->content2 = new Content();
        $this->content2->setContentType($this->contentType2);

        $admin = new ApiClient();
        $admin->setRoles([Domain::ROLE_ADMINISTRATOR]);
        $admin->setDomain($this->domain1);
        $this->u['domain_admin'] = new UsernamePasswordToken($admin, 'password', 'main', []);

        $user = new ApiClient();
        $user->setRoles([Domain::ROLE_EDITOR]);
        $user->setDomain($this->domain1);
        $this->u['domain_editor'] = new UsernamePasswordToken($user, 'password', 'main', []);
    }

    public function testCRUDActions()
    {

        $dm = $this->container->get('security.authorization_checker');

        // All other users can preform the actions they have access to.
        $this->container->get('security.token_storage')->setToken($this->u['domain_admin']);
        $this->assertTrue($dm->isGranted([ContentVoter::LIST], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::CREATE], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::VIEW], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::UPDATE], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::DELETE], $this->content1));

        $this->assertFalse($dm->isGranted([ContentVoter::LIST], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::VIEW], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content2));

        $this->container->get('security.token_storage')->setToken($this->u['domain_editor']);
        $this->assertTrue($dm->isGranted([ContentVoter::LIST], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::CREATE], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::VIEW], $this->content1));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content1));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content1));

        $this->assertFalse($dm->isGranted([ContentVoter::LIST], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::VIEW], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content2));
    }

    public function testCRUDActionsForDeletedContent()
    {

        $dm = $this->container->get('security.authorization_checker');

        $reflector = new \ReflectionProperty(Content::class, 'deleted');
        $reflector->setAccessible(true);
        $reflector->setValue($this->content1, new \DateTime());
        $reflector->setValue($this->content2, new \DateTime());

        $this->container->get('security.token_storage')->setToken($this->u['domain_admin']);
        $this->assertTrue($dm->isGranted([ContentVoter::LIST], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::CREATE], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::VIEW], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::UPDATE], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::DELETE], $this->content1));

        $this->assertFalse($dm->isGranted([ContentVoter::LIST], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::VIEW], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content2));

        $this->container->get('security.token_storage')->setToken($this->u['domain_editor']);
        $this->assertTrue($dm->isGranted([ContentVoter::LIST], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::CREATE], $this->contentType1));
        $this->assertFalse($dm->isGranted([ContentVoter::VIEW], $this->content1));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content1));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content1));

        $this->assertFalse($dm->isGranted([ContentVoter::LIST], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::VIEW], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content2));
    }
}