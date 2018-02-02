<?php

namespace UnitedCMS\CoreBundle\SchemaType\Types;

use Doctrine\ORM\EntityManager;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Form\FieldableFormBuilder;
use UnitedCMS\CoreBundle\Security\ContentVoter;
use UnitedCMS\CoreBundle\Service\UnitedCMSManager;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

class MutationType extends AbstractType
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
     * @var ValidatorInterface $validator
     */
    private $validator;

    /**
     * @var FieldableFormBuilder $fieldableFormBuilder
     */
    private $fieldableFormBuilder;

    public function __construct(
        SchemaTypeManager $schemaTypeManager,
        EntityManager $entityManager,
        UnitedCMSManager $unitedCMSManager,
        AuthorizationChecker $authorizationChecker,
        ValidatorInterface $validator,
        FieldableFormBuilder $fieldableFormBuilder
    ) {
        $this->schemaTypeManager = $schemaTypeManager;
        $this->entityManager = $entityManager;
        $this->unitedCMSManager = $unitedCMSManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->validator = $validator;
        $this->fieldableFormBuilder = $fieldableFormBuilder;
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

        // Append Content types.
        foreach ($this->unitedCMSManager->getDomain()->getContentTypes() as $contentType) {
            $key = ucfirst($contentType->getIdentifier());

            $fields['create' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'Content', $this->unitedCMSManager->getDomain()),
            ];

            $fields['update' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'Content', $this->unitedCMSManager->getDomain()),
                'args' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'The id of the content item to get.',
                    ],
                ],
            ];

            // If this content type has defined fields, we can create and update content with data.
            $fullContentType = $this->entityManager->getRepository('UnitedCMSCoreBundle:ContentType')->find($contentType->getId());
            if($fullContentType->getFields()->count() > 0) {
                $fields['create' . $key]['args']['data'] = [
                    'type' => Type::nonNull($this->schemaTypeManager->getSchemaType($key . 'ContentInput', $this->unitedCMSManager->getDomain())),
                    'description' => 'The content data to save.',
                ];
                $fields['update' . $key]['args']['data'] = [
                    'type' => Type::nonNull($this->schemaTypeManager->getSchemaType($key . 'ContentInput', $this->unitedCMSManager->getDomain())),
                    'description' => 'The content data to save.',
                ];
            }
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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function resolveField($value, array $args, $context, ResolveInfo $info)
    {
        // Resolve create content type
        if(substr($info->fieldName, 0, 6) == 'create') {
            return $this->resolveCreateContent(
                strtolower(substr($info->fieldName, 6)),
                $value, $args, $context, $info
            );
        }

        // Resolve update content type
        elseif(substr($info->fieldName, 0, 6) == 'update') {
            return $this->resolveUpdateContent(
                strtolower(substr($info->fieldName, 6, -strlen('Content'))),
                $value, $args, $context, $info
            );
        }

        return null;
    }


    /**
     * Resolve create content.
     *
     * @param $identifier
     * @param $value
     * @param $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function resolveCreateContent($identifier, $value, $args, $context, ResolveInfo $info) {

        if (!$contentType = $this->entityManager->getRepository('UnitedCMSCoreBundle:ContentType')->findOneBy(
            [
                'domain' => $this->unitedCMSManager->getDomain(),
                'identifier' => $identifier,
            ]
        )) {
            throw new UserError("ContentType '$identifier' was not found in domain.");
        }

        if (!$this->authorizationChecker->isGranted(ContentVoter::CREATE, $contentType)) {
            throw new UserError("You are not allowed to create content in content type '$contentType'.");
        }

        $content = new Content();
        $form = $this->fieldableFormBuilder->createForm($contentType, $content, ['csrf_protection' => false]);
        $form->submit($args['data']);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            if (isset($data['locale'])) {
                $content->setLocale($data['locale']);
                unset($data['locale']);
            }

            $content
                ->setContentType($contentType)
                ->setData($data);

            // If content errors were found, map them to the form.
            $violations = $this->validator->validate($content);

            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

            // If content is valid.
            } else {
                $this->entityManager->persist($content);
                $this->entityManager->flush();

                return $content;
            }
        }

        throw new UserError($form->getErrors(true, true));
    }

    /**
     * Resolve update content.
     *
     * @param $identifier
     * @param $value
     * @param $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function resolveUpdateContent($identifier, $value, $args, $context, ResolveInfo $info) {

        $id = $args['id'];
        $content = $this->entityManager->getRepository('UnitedCMSCoreBundle:Content')->find($id);

        if(!$content) {
            throw new UserError("Content was not found.");
        }

        if (!$this->authorizationChecker->isGranted(ContentVoter::UPDATE, $content)) {
            throw new UserError("You are not allowed to update content with id '$id'.");
        }

        $form = $this->fieldableFormBuilder->createForm($content->getContentType(), $content, ['csrf_protection' => false]);
        $form->submit($args['data']);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            if (isset($data['locale'])) {
                $content->setLocale($data['locale']);
                unset($data['locale']);
            }

            $content->setData($data);

            // If content errors were found, map them to the form.
            $violations = $this->validator->validate($content);
            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

            // If content is valid.
            } else {
                $this->entityManager->flush();
                return $content;
            }
        }

        throw new UserError($form->getErrors(true, true));
    }
}