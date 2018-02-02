<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 02.02.18
 * Time: 09:15
 */

namespace UnitedCMS\CoreBundle\Tests\View;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\User;
use UnitedCMS\CoreBundle\Entity\View;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;
use UnitedCMS\CoreBundle\View\ViewType;

class ViewMutationTypesTest extends DatabaseAwareTestCase
{

    public function testResolveUnknownMutationSchemaType() {

        $info = new ResolveInfo(['fieldName' => 'foo']);
        $view = new View();
        $view->setType('table')->setIdentifier('foo')->setContentType(new ContentType())->getContentType()->setIdentifier('baa');
        $this->assertNull($this->container->get('united.cms.view_type_manager')->resolveMutationSchemaType($view, null, [], null, $info));

        $info->fieldName = 'FooBaa';
        $this->assertNull($this->container->get('united.cms.view_type_manager')->resolveMutationSchemaType($view, null, [], null, $info));

        $info->fieldName = 'anyFooBaa';
        $this->assertNull($this->container->get('united.cms.view_type_manager')->resolveMutationSchemaType($view, null, [], null, $info));

    }

    public function testViewsCanDefineMutationTypes() {

        // Create a viewType, that provides mutation schema types.
        $testViewType = new class extends ViewType {
            const TYPE = "TEST VIEW TYPE";
            public $myValue;

            public function getMutationSchemaTypes(SchemaTypeManager $schemaTypeManager) : array {
                return [
                    'fooupdate' => [
                        'type' => Type::string(),
                        'args' => [
                            'foo' => [
                                'type' => Type::nonNull(Type::string()),
                                'description' => 'Any description',
                            ],
                        ],
                    ],
                ];
            }

            public function resolveMutationSchemaType($action, $value, array $args, $context, ResolveInfo $info) {
                if($action === 'fooupdate') {
                    return $args['foo'] . $this->myValue;
                }
                return null;
            }
        };
        $testViewType->myValue = $this->generateRandomUTF8String(50);

        // Register the view type.
        $this->container->get('united.cms.view_type_manager')->registerViewType($testViewType);

        // Define a content type, that uses our view.
        $ct = new ContentType();
        $ct->setIdentifier('ct1')->setTitle('CT 1');
        $ct->getView('all')->setType($testViewType::TYPE);
        $domain = new Domain();
        $domain->setIdentifier('d1')->setTitle('D 1')->addContentType($ct)->setOrganization(new Organization())
            ->getOrganization()->setIdentifier('o1')->setTitle('O 1');

        $this->em->persist($domain->getOrganization());
        $this->em->persist($domain);
        $this->em->persist($ct);
        $this->em->flush();

        // Inject created domain into untied.cms.manager.
        $d = new \ReflectionProperty($this->container->get('united.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue($this->container->get('united.cms.manager'), $domain);

        // In this test, we don't care about access checking.
        $admin = new User();
        $admin->setRoles([User::ROLE_PLATFORM_ADMIN]);
        $this->container->get('security.token_storage')->setToken(new UsernamePasswordToken($admin, null, 'main', $admin->getRoles()));

        // Create a mutation schema from domain.
        $schemaTypeManager = $this->container->get('united.cms.graphql.schema_type_manager');
        $schema = new Schema([
            'query' => $schemaTypeManager->getSchemaType('Query'),
            'mutation' => $schemaTypeManager->getSchemaType('Mutation'),
            'typeLoader' => function ($name) use ($schemaTypeManager, $domain) {
                return $schemaTypeManager->getSchemaType($name, $domain);
            },]
        );

        // There should be a mutation for fooupdateAllCt1, that returns the value from our testViewType.
        $result = GraphQL::executeQuery($schema, 'mutation { fooupdateAllCt1(foo: "myFooValue") }');
        $this->assertEquals(['data' => [
            'fooupdateAllCt1' => 'myFooValue' . $testViewType->myValue,
        ]], $result->toArray(true));
    }

}