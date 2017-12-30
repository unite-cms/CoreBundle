<?php

namespace UnitedCMS\CoreBundle\Tests\Controller;

use Doctrine\ORM\Id\UuidGenerator;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\DomainMember;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\OrganizationMember;
use UnitedCMS\CoreBundle\Entity\User;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class ContentControllerTest extends DatabaseAwareTestCase {

    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var User $editor
     */
    private $editor;

    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var Domain $domain
     */
    private $domain;

    /**
     * @var string
     */
    private $domainConfiguration = '{
    "title": "Test controller access check domain",
    "identifier": "access_check", 
    "content_types": [
      {
        "title": "CT 1",
        "identifier": "ct1", 
        "fields": [
            { "title": "Field 1", "identifier": "f1", "type": "text" }, 
            { "title": "Field 2", "identifier": "f2", "type": "text" }
        ], 
        "collections": [
            { "title": "All", "identifier": "all", "type": "table" },
            { "title": "Other", "identifier": "other", "type": "table" }
        ]
      }
    ], 
    "setting_types": [
      {
        "title": "ST 1",
        "identifier": "st1", 
        "fields": [
            { "title": "Field 1", "identifier": "f1", "type": "text" }, 
            { "title": "Field 2", "identifier": "f2", "type": "text" }
        ]
      }
    ]
  }';

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->container->get('test.client');
        $this->client->followRedirects(false);

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Organization')->setIdentifier('org1');
        $this->domain = $this->container->get('united.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $this->domain->setOrganization($this->organization);

        $this->em->persist($this->organization);
        $this->em->persist($this->domain);
        $this->em->flush();
        $this->em->refresh($this->organization);
        $this->em->refresh($this->domain);

        $this->editor = new User();
        $this->editor->setEmail('editor@example.com')->setFirstname('Domain Editor')->setLastname('Example')->setRoles([User::ROLE_USER])->setPassword('XXX');
        $domainEditorOrgMember = new OrganizationMember();
        $domainEditorOrgMember->setRoles([Organization::ROLE_USER])->setOrganization($this->organization);
        $domainEditorDomainMember = new DomainMember();
        $domainEditorDomainMember->setRoles([Domain::ROLE_EDITOR])->setDomain($this->domain);
        $this->editor->addOrganization($domainEditorOrgMember);
        $this->editor->addDomain($domainEditorDomainMember);

        $this->em->persist($this->editor);
        $this->em->flush();
        $this->em->refresh($this->editor);

        $token = new UsernamePasswordToken($this->editor, null, 'main', $this->editor->getRoles());
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testCRUDActions() {

        $url_other_list = $this->container->get('router')->generate('unitedcms_core_content_index', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'collection' => 'other',
        ]);

        $this->client->request('GET', $url_other_list);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $url_list = $this->container->get('router')->generate('unitedcms_core_content_index', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'collection' => 'all',
        ]);

        $crawler = $this->client->request('GET', $url_list);

        // Assert add button.
        $addButton = $crawler->filter('a.uk-icon-button.uk-button-primary');
        $this->assertCount(1, $addButton);

        // Click on add button.
        $crawler = $this->client->click($addButton->first()->link());

        // Assert add form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit form
        $form = $form->form();
        $form['fieldable_form[f1]'] = 'Field value 1';
        $form['fieldable_form[f2]'] = 'Field value 2';
        $this->client->submit($form);

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect($url_list));
        $crawler = $this->client->followRedirect();

        // Assert creation message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Content created.")'));

        // Since the collection list is rendered in js, we can't check creation via DOM. But we can see, if we can edit
        // the content.
        $content = $this->em->getRepository('UnitedCMSCoreBundle:Content')->findOneBy([ 'contentType' => $this->domain->getContentTypes()->first(), ], [ 'created' => 'DESC', ]);
        $this->assertNotNull($content);
        $this->assertEquals('Field value 1', $content->getData()['f1']);
        $this->assertEquals('Field value 2', $content->getData()['f2']);

        // Try to update invalid content
        $doctrineUUIDGenerator = new UuidGenerator();
        $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_update', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'collection' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ]));

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to update valid content
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_update', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'collection' => 'all',
            'content' => $content->getId()
        ]));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Assert add form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit form
        $form = $form->form();
        $form['fieldable_form[f1]'] = 'Updated Field value 1';
        $form['fieldable_form[f2]'] = 'Updated Field value 2';
        $this->client->submit($form);

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect($url_list));
        $crawler = $this->client->followRedirect();

        // Assert creation message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Content updated.")'));

        // Update content.
        $this->em->refresh($content);
        $this->assertEquals('Updated Field value 1', $content->getData()['f1']);
        $this->assertEquals('Updated Field value 2', $content->getData()['f2']);


        // Try to delete invalid content.
        $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_delete', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'collection' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ]));
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to delete valid content
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_delete', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'collection' => 'all',
            'content' => $content->getId()
        ]));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Assert add form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit form
        $form = $form->form();
        $this->client->submit($form);

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect($url_list));
        $crawler = $this->client->followRedirect();

        // Assert creation message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Content deleted.")'));

        $this->assertCount(0, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());
    }
}