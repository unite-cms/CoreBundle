<?php

namespace UnitedCMS\CoreBundle\SchemaType\Types;

use Doctrine\ORM\EntityManager;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Knp\Component\Pager\Pagination\AbstractPagination;
use Knp\Component\Pager\Paginator;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UnitedCMS\CoreBundle\Entity\Collection;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentInCollection;
use UnitedCMS\CoreBundle\Entity\Setting;
use UnitedCMS\CoreBundle\Form\FieldableFormBuilder;
use UnitedCMS\CoreBundle\Security\ContentVoter;
use UnitedCMS\CoreBundle\Security\SettingVoter;
use UnitedCMS\CoreBundle\Service\UnitedCMSManager;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UnitedCMS\CoreBundle\Service\GraphQLDoctrineFilterQueryBuilder;

class QueryType extends AbstractType
{


    /**
     * @var SchemaTypeManager $schemaTypeManager
     */
    private $schemaTypeManager;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var UnitedCMSManager $unitedCMSManager
     */
    private $unitedCMSManager;

    /**
     * @var AuthorizationChecker $authorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var Paginator $paginator
     */
    private $paginator;

    public function __construct(
        SchemaTypeManager $schemaTypeManager,
        EntityManager $entityManager,
        UnitedCMSManager $unitedCMSManager,
        AuthorizationChecker $authorizationChecker,
        Paginator $paginator
    ) {
        $this->schemaTypeManager = $schemaTypeManager;
        $this->entityManager = $entityManager;
        $this->unitedCMSManager = $unitedCMSManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->paginator = $paginator;
        parent::__construct();
    }

    /**
     * Define all fields of this type.
     *
     * @return array
     */
    protected function fields()
    {
        $fields = [];
        $fields['find'] = [
            'type' => $this->schemaTypeManager->getSchemaType('ContentResultInterface'),
            'args' => [
                'limit' => [
                    'type' => Type::int(),
                    'description' => 'Set maximal number of content items to return.',
                    'defaultValue' => 20,
                ],
                'page' => [
                    'type' => Type::int(),
                    'description' => 'Set the pagination page to get the content from.',
                    'defaultValue' => 1,
                ],
                'sort' => [
                    'type' => Type::listOf($this->schemaTypeManager->getSchemaType('SortInput')),
                    'description' => 'Set one or many fields to sort by.',
                ],
                'filter' => [
                    'type' => $this->schemaTypeManager->getSchemaType('FilterInput'),
                    'description' => 'Set one optional filter condition.',
                ],
                'types' => [
                    'type' => Type::listOf($this->schemaTypeManager->getSchemaType('ContentTypeCollectionInput')),
                    'description' => 'Set all additional content type and collection tuple to get content from. With this option you can get content from multiple content types and/or collections.',
                ],
                'deleted' => [
                    'type' => Type::boolean(),
                    'description' => 'Also show deleted entries. Only user who can also update content can view deleted content.',
                    'defaultValue' => false,
                ],
            ],
        ];

        // Append Content types.
        foreach ($this->unitedCMSManager->getDomain()->getContentTypes() as $contentType) {
            $key = ucfirst($contentType->getIdentifier());
            $fields['get' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'Content', $this->unitedCMSManager->getDomain()),
                'args' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'The id of the content item to get.',
                    ],
                ],
            ];

            $fields['find' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'ContentResult', $this->unitedCMSManager->getDomain()),
                'args' => [
                    'collection' => [
                        'type' => Type::string(),
                        'description' => 'Set the collection to get content form. Default is "all".',
                        'defaultValue' => Collection::DEFAULT_COLLECTION_IDENTIFIER,
                    ],
                    'limit' => [
                        'type' => Type::int(),
                        'description' => 'Set maximal number of content items to return.',
                        'defaultValue' => 20,
                    ],
                    'page' => [
                        'type' => Type::int(),
                        'description' => 'Set the pagination page to get the content from.',
                        'defaultValue' => 1,
                    ],
                    'sort' => [
                        'type' => Type::listOf($this->schemaTypeManager->getSchemaType('SortInput')),
                        'description' => 'Set one or many fields to sort by.',
                    ],
                    'filter' => [
                        'type' => $this->schemaTypeManager->getSchemaType('FilterInput'),
                        'description' => 'Set one optional filter condition.',
                    ],
                    'deleted' => [
                        'type' => Type::boolean(),
                        'description' => 'Also show deleted entries. Only user who can also update content can view deleted content.',
                        'defaultValue' => false,
                    ],
                ],
            ];
        }

        // Append Setting types.
        foreach ($this->unitedCMSManager->getDomain()->getSettingTypes() as $settingType) {
            $key = ucfirst($settingType->getIdentifier()) . 'Setting';
            $fields[$key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key, $this->unitedCMSManager->getDomain()),
            ];
        }

        return $fields;
    }

    /**
     * Resolve fields for this type.
     * Returns the object or scalar value for the field, define in $info.
     *
     * @param mixed $value
     * @param array $args
     * @param $context
     * @param ResolveInfo $info
     *
     * @return mixed
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    protected function resolveField($value, array $args, $context, ResolveInfo $info)
    {
        // Resolve single content type.
        if(substr($info->fieldName, 0, 3) == 'get') {

            $id = $args['id'];
            $content = $this->entityManager->getRepository('UnitedCMSCoreBundle:Content')->find($id);

            if ($content && !$this->authorizationChecker->isGranted(ContentVoter::VIEW, $content)) {
                throw new UserError("You are not allowed to view content with id '$id'.");
            }

            return $content;
        }

        // Resolve single setting type.
        elseif (substr($info->fieldName, -strlen('Setting')) === 'Setting') {
            return $this->resolveSetting(strtolower(substr($info->fieldName, 0, -strlen('Setting'))), $value, $args, $context, $info);
        }

        // Resolve list content type.
        elseif(substr($info->fieldName, 0, 4) == 'find' && strlen($info->fieldName) > 4) {
            $args['types'] = [[
                'type' => strtolower(substr($info->fieldName, 4)),
                'collection' => $args['collection'],
            ]];
            unset($args['collection']);
            return $this->resolveContent(substr($info->fieldName, 4) . 'ContentResult',  $value, $args, $context, $info);
        }

        // Resolve generic find type
        elseif(substr($info->fieldName, 0, 4) == 'find' && strlen($info->fieldName) == 4) {
            return $this->resolveContent('ContentResult', $value, $args, $context, $info);
        }

        return null;
    }

    /**
     * Resolve the content results.
     *
     * @param $resultType
     * @param $value
     * @param array $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    private function resolveContent($resultType, $value, array $args, $context, ResolveInfo $info) : AbstractPagination
    {

        $args['types'] = $args['types'] ?? [];
        $args['limit'] = $args['limit'] < 0 ? 0 : $args['limit'];
        $args['limit'] = $args['limit'] > 100 ? 100 : $args['limit'];
        $args['page'] = $args['page'] < 1 ? 1 : $args['page'];
        $args['deleted'] = $args['deleted'] ?? false;

        // Get all requested collections the user can access.
        $collections = [];
        foreach ($args['types'] as $type) {
            if ($collection = $this->entityManager->getRepository('UnitedCMSCoreBundle:Collection')->findByIdentifiers(
                $this->unitedCMSManager->getOrganization()->getIdentifier(),
                $this->unitedCMSManager->getDomain()->getIdentifier(),
                $type['type'],
                $type['collection']
            )) {
                if ($this->authorizationChecker->isGranted(ContentVoter::LIST, $collection)) {
                    $collections[] = $collection;
                }
            }
        }

        // Get content from all collections
        $contentEntityFields = $this->entityManager->getClassMetadata(Content::class)->getFieldNames();
        $contentQuery = $this->entityManager->getRepository(
            'UnitedCMSCoreBundle:Content'
        )->createQueryBuilder('c')
            ->select('c')
            ->where('co.collection IN (:collections)')
            ->setParameter(':collections', $collections)
            ->leftJoin('c.collections', 'co');

        // Sorting by nested data attributes is not possible with knp paginator, so we need to do it manually.
        if (!empty($args['sort'])) {
            foreach ($args['sort'] as $sort) {

                $key = $sort['field'];
                $order = $sort['order'];

                // TODO: Allow to sort by collection settings

                // if we sort by a content field.
                if (in_array($key, $contentEntityFields)) {
                    $contentQuery->addOrderBy('c.'.$key, $order);

                // if we sort by a nested content data field.
                } else {
                    $contentQuery->addOrderBy("JSON_EXTRACT(c.data, '$.$key')", $order);
                }
            }
        }

        // Adding where filter to the query.
        if (!empty($args['filter'])) {


            // The filter array can contain a direct filter or multiple nested AND or OR filters. But only one of this cases.

            // TODO: Replace field names with nested field selectors.

            $a = new GraphQLDoctrineFilterQueryBuilder($args['filter'], $contentEntityFields, 'c');
            $contentQuery->andWhere($a->getFilter());
            foreach($a->getParameters() as $parameter => $value) {
                $contentQuery->setParameter($parameter, $value);
            }
        }

        // Also show deleted content.
        if($args['deleted']) {
            $this->entityManager->getFilters()->disable('gedmo_softdeleteable');
        }

        // Get all content in one request for all collections.
        $pagination = $this->paginator->paginate($contentQuery, $args['page'], $args['limit'], ['alias' => $resultType]);

        if($args['deleted']) {
            // We need to clear content cache, so deleted entities will not be shown on next turn.
            $this->entityManager->clear(Content::class);
            $this->entityManager->getFilters()->enable('gedmo_softdeleteable');
        }

        return $pagination;
    }

    /**
     * Resolve the setting result.
     *
     * @param $identifier
     * @param $value
     * @param $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     */
    private function resolveSetting($identifier, $value, $args, $context, ResolveInfo $info) : Setting
    {
        if (!$settingType = $this->entityManager->getRepository('UnitedCMSCoreBundle:SettingType')->findOneBy(
            [
                'domain' => $this->unitedCMSManager->getDomain(),
                'identifier' => $identifier,
            ]
        )) {
            throw new UserError("SettingType '$identifier' was not found in domain.");
        }


        /**
         * @var \UnitedCMS\CoreBundle\Entity\Setting $setting
         */
        $setting = $settingType->getSetting();

        if (!$this->authorizationChecker->isGranted(SettingVoter::VIEW, $setting)) {
            throw new UserError("You are not allowed to view setting of type '$identifier'.");
        }

        // Create setting schema type for current domain.
        $type = ucfirst($setting->getSettingType()->getIdentifier()).'Setting';
        $this->schemaTypeManager->getSchemaType($type, $this->unitedCMSManager->getDomain());

        return $setting;
    }
}