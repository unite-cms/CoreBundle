<?php

namespace UniteCMS\CoreBundle\Field\Types;

use App\Bundle\CoreBundle\Exception\ContentAccessDeniedException;
use App\Bundle\CoreBundle\Exception\ContentTypeAccessDeniedException;
use App\Bundle\CoreBundle\Exception\DomainAccessDeniedException;
use App\Bundle\CoreBundle\Exception\InvalidFieldConfigurationException;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Form\ReferenceType;
use UniteCMS\CoreBundle\Security\Voter\DomainVoter;
use UniteCMS\CoreBundle\View\ViewTypeInterface;
use UniteCMS\CoreBundle\View\ViewTypeManager;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class ReferenceFieldType extends FieldType
{
    const TYPE = "reference";
    const FORM_TYPE = ReferenceType::class;
    const SETTINGS = ['domain', 'content_type', 'view', 'content_label'];
    const REQUIRED_SETTINGS = ['domain', 'content_type'];

    private $validator;
    private $authorizationChecker;
    private $uniteCMSManager;
    private $viewTypeManager;
    private $entityManager;
    private $templating;
    private $csrfTokenManager;

    function __construct(
        ValidatorInterface $validator,
        AuthorizationChecker $authorizationChecker,
        UniteCMSManager $uniteCMSManager,
        EntityManager $entityManager,
        ViewTypeManager $viewTypeManager,
        TwigEngine $templating,
        CsrfTokenManager $csrfTokenManager
    ) {
        $this->validator = $validator;
        $this->authorizationChecker = $authorizationChecker;
        $this->uniteCMSManager = $uniteCMSManager;
        $this->viewTypeManager = $viewTypeManager;
        $this->entityManager = $entityManager;
        $this->templating = $templating;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Resolves an content type and checks permission for the domain.
     *
     * @param string $domain_identifier
     * @param string $content_type_identifier
     * @return ContentType
     * @throws InvalidFieldConfigurationException
     * @throws DomainAccessDeniedException
     */
    private function resolveContentType($domain_identifier, $content_type_identifier): ContentType
    {

        if (!$domain_identifier || !$content_type_identifier) {
            throw new InvalidArgumentException("You must pass a domain and content_type identifier.");
        }

        // Only allow to resolve a content type from the same organization.
        $organization = $this->uniteCMSManager->getOrganization();

        $domain = $organization->getDomains()->filter(
            function (Domain $domain) use ($domain_identifier) {
                return $domain->getIdentifier() == $domain_identifier;
            }
        )->first();

        if (!$domain) {
            throw new InvalidFieldConfigurationException(
                "A reference field was configured to reference to domain \"{$domain_identifier}\". However \"{$domain_identifier}\" does not exist, or you don't have access to it."
            );
        }

        // We need to reload the full domain. uniteCMSManager only holds infos for the current domain.
        $domain = $this->entityManager->getRepository('UniteCMSCoreBundle:Domain')->findOneBy(
            [
                'organization' => $organization,
                'id' => $domain->getId(),
            ]
        );

        if (!$this->authorizationChecker->isGranted(DomainVoter::VIEW, $domain)) {
            throw new DomainAccessDeniedException(
                "A reference field was configured to reference to domain \"{$domain_identifier}\". However you are not allowed to access it."
            );
        }

        $contentType = $domain->getContentTypes()->filter(
            function (ContentType $contentType) use ($content_type_identifier) {
                return $contentType->getIdentifier() == $content_type_identifier;
            }
        )->first();

        if (!$contentType) {
            throw new InvalidFieldConfigurationException(
                "A reference field was configured to reference to content type \"{$content_type_identifier}\" on domain \"{$domain_identifier}\". However \"{$content_type_identifier}\" does not exist."
            );
        }

        return $contentType;
    }

    /**
     * {@inheritdoc}
     * @throws ContentTypeAccessDeniedException
     * @throws InvalidFieldConfigurationException
     * @throws \Twig\Error\Error
     * @throws DomainAccessDeniedException
     */
    function getFormOptions(FieldableField $field): array
    {
        $settings = $field->getSettings();
        $settings->view = $settings->view ?? 'all';

        // Get content type and check if we have access to it.
        $contentType = $this->resolveContentType($settings->domain, $settings->content_type);
        if (!$this->authorizationChecker->isGranted(ContentVoter::LIST, $contentType)) {
            throw new ContentTypeAccessDeniedException("You are not allowed to view the content type \"{$settings->content_type}\".");
        }

        // Get view.
        $view = $contentType->getViews()->filter(
            function (View $view) use ($settings) {
                return $view->getIdentifier() == $settings->view;
            }
        )->first();
        if (!$view) {
            throw new InvalidFieldConfigurationException(
                "No view with identifier '{$settings->view}' was found for this organization, domain and content type."
            );
        }

        // Reload the full view object.
        $view = $this->entityManager->getRepository('UniteCMSCoreBundle:View')->findOneBy(
            [
                'contentType' => $contentType,
                'id' => $view->getId(),
            ]
        );

        // Pass the rendered view HTML and other parameters as a form option.
        return array_merge(
            parent::getFormOptions($field),
            [
                'empty_data' => [
                    'domain' => $contentType->getDomain()->getIdentifier(),
                    'content_type' => $contentType->getIdentifier(),
                ],
                'attr' => [
                    'base-url' => '/'.$this->uniteCMSManager->getOrganization()->getIdentifier().'/',
                    'content-label' => $settings->content_label ?? (empty(
                        $contentType->getContentLabel()
                        ) ? (string)$contentType.' #{id}' : $contentType->getContentLabel()),
                    'modal-html' => $this->templating->render(
                        $this->viewTypeManager->getViewType($view->getType())::getTemplate(),
                        [
                            'view' => $view,
                            'parameters' => $this->viewTypeManager
                                ->getTemplateRenderParameters($view, ViewTypeInterface::SELECT_MODE_SINGLE)
                                ->setCsrfToken($this->csrfTokenManager->getToken('fieldable_form')),
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     * @throws InvalidFieldConfigurationException
     * @throws ContentTypeAccessDeniedException
     * @throws DomainAccessDeniedException
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {

        // Get content type and check if we have access to it.
        $contentType = $this->resolveContentType($field->getSettings()->domain, $field->getSettings()->content_type);

        if (!$this->authorizationChecker->isGranted(ContentVoter::LIST, $contentType)) {
            throw new ContentTypeAccessDeniedException("You are not allowed to list content of content type \"{$contentType->getIdentifier()}\" on domain \"{$contentType->getDomain()->getIdentifier()}\".");
        }

        $name = ucfirst($field->getSettings()->content_type.'Content');

        if ($nestingLevel > 0) {
            $name .= 'Level'.$nestingLevel;
        }

        // We use the default content factory to build the type.
        return $schemaTypeManager->getSchemaType($name, $contentType->getDomain(), $nestingLevel);
    }

    /**
     * {@inheritdoc}
     * @throws InvalidFieldConfigurationException
     * @throws ContentTypeAccessDeniedException
     * @throws DomainAccessDeniedException
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {

        // Get content type and check if we have access to it.
        $contentType = $this->resolveContentType($field->getSettings()->domain, $field->getSettings()->content_type);
        if (!$this->authorizationChecker->isGranted(ContentVoter::LIST, $contentType)) {
            throw new ContentTypeAccessDeniedException("You are not allowed to view the content type \"{$contentType}\".");
        }

        return $schemaTypeManager->getSchemaType('ReferenceFieldTypeInput', $contentType->getDomain(), $nestingLevel);
    }

    /**
     * Resolve reference data. This means getting the referenced entity, checking access and returning it.
     *
     * @param FieldableField $field
     * @param array $value
     * @return null|Content
     *
     * @throws InvalidFieldConfigurationException
     * @throws ContentAccessDeniedException
     * @throws DomainAccessDeniedException
     */
    function resolveGraphQLData(FieldableField $field, $value)
    {
        if (empty($value)) {
            return null;
        }

        $contentType = $this->resolveContentType($value['domain'], $value['content_type']);

        // Find content for this content type.
        $content = $this->entityManager->getRepository('UniteCMSCoreBundle:Content')->findOneBy(
            ['contentType' => $contentType, 'id' => $value['content']]
        );
        if (!$content) {
            throw new InvalidArgumentException("No content with id '{$value['content']}' was found.");
        }

        // Check access to view content.
        if (!$this->authorizationChecker->isGranted(ContentVoter::VIEW, $content)) {
            throw new ContentAccessDeniedException("You are not allowed to view this content.");
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     *
     */
    function validateData(FieldableField $field, $data, $validation_group = 'DEFAULT'): array
    {

        // When deleting content, we don't need to validate data.
        if ($validation_group === 'DELETE') {
            return [];
        }

        $violations = [];

        // Only validate available data.
        if (empty($data)) {
            return $violations;
        }

        // Make sure, that all required fields are set.
        if (empty($data['domain']) || empty($data['content_type']) || empty($data['content'])) {
            $violations[] = $this->createViolation($field, 'validation.missing_definition');
        } // Try to resolve the data to check if the current user is allowed to access it.
        else {
            try {
                $this->resolveGraphQLData($field, $data);
            } catch (\Exception $e) {
                $violations[] = $this->createViolation($field, 'validation.wrong_definition');
            }
        }

        return $violations;
    }
}
