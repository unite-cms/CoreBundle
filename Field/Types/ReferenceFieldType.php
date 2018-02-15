<?php

namespace UnitedCMS\CoreBundle\Field\Types;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Form\ReferenceType;
use UnitedCMS\CoreBundle\View\ViewTypeInterface;
use UnitedCMS\CoreBundle\View\ViewTypeManager;
use UnitedCMS\CoreBundle\Entity\View;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Field\FieldType;
use UnitedCMS\CoreBundle\Security\ContentVoter;
use UnitedCMS\CoreBundle\Security\DomainVoter;
use UnitedCMS\CoreBundle\Service\UnitedCMSManager;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

class ReferenceFieldType extends FieldType
{
    const TYPE                      = "reference";
    const FORM_TYPE                 = ReferenceType::class;
    const SETTINGS                  = ['domain', 'content_type', 'view', 'content_label'];
    const REQUIRED_SETTINGS         = ['domain', 'content_type'];

    private $validator;
    private $authorizationChecker;
    private $unitedCMSManager;
    private $viewTypeManager;
    private $entityManager;
    private $templating;

    function __construct(ValidatorInterface $validator, AuthorizationChecker $authorizationChecker, UnitedCMSManager $unitedCMSManager, EntityManager $entityManager, ViewTypeManager $viewTypeManager, TwigEngine $templating) {
        $this->validator = $validator;
        $this->authorizationChecker = $authorizationChecker;
        $this->unitedCMSManager = $unitedCMSManager;
        $this->viewTypeManager = $viewTypeManager;
        $this->entityManager = $entityManager;
        $this->templating = $templating;
    }

    /**
     * Resolves an content type and checks permission for the domain.
     *
     * @param string $domain_identifier
     * @param string $content_type_identifier
     * @return ContentType
     */
    private function resolveContentType($domain_identifier, $content_type_identifier) : ContentType {

        if(!$domain_identifier || !$content_type_identifier) {
            throw new InvalidArgumentException("You must pass a domain and content_type identifier.");
        }

        // Only allow to resolve a content type from the same organization.
        $organization = $this->unitedCMSManager->getOrganization();

        $domain = $organization->getDomains()->filter(function( Domain $domain ) use($domain_identifier) { return $domain->getIdentifier() == $domain_identifier; })->first();

        if(!$domain) {
            throw new InvalidArgumentException("No domain with identifier '{$domain_identifier}' was found in this organization.");
        }

        if(!$this->authorizationChecker->isGranted(DomainVoter::VIEW, $domain)) {
            throw new InvalidArgumentException("You are not allowed to view this domain.");
        }

        $contentType = $domain->getContentTypes()->filter(function( ContentType $contentType ) use($content_type_identifier) { return $contentType->getIdentifier() == $content_type_identifier; })->first();

        if(!$contentType) {
            throw new InvalidArgumentException("No content_type with identifier '{$content_type_identifier}' was found for this organization and domain.");
        }

        return $contentType;
    }

    /**
     * @return array
     * @throws \Twig\Error\Error|InvalidArgumentException
     */
    function getFormOptions(): array
    {
        $settings = $this->field->getSettings();
        $settings->view = $settings->view ?? 'all';

        // Get content type and check if we have access to it.
        $contentType = $this->resolveContentType($settings->domain, $settings->content_type);
        if(!$this->authorizationChecker->isGranted(ContentVoter::LIST, $contentType)) {
            throw new InvalidArgumentException("You are not allowed to view this content_type.");
        }

        // Get view.
        $view = $contentType->getViews()->filter(function( View $view) use($settings) { return $view->getIdentifier() == $settings->view; })->first();
        if(!$view) {
            throw new InvalidArgumentException("No view with identifier '{$settings->view}' was found for this organization, domain and content type.");
        }

        // Reload the full view object.
        $view = $this->entityManager->getRepository('UnitedCMSCoreBundle:View')->findOneBy([
            'contentType' => $contentType,
            'id' => $view->getId(),
        ]);

        // Pass the rendered view HTML and other parameters as a form option.
        return array_merge(parent::getFormOptions(), [
            'empty_data' => [
                'domain' => $contentType->getDomain()->getIdentifier(),
                'content_type' => $contentType->getIdentifier(),
            ],
            'attr' => [
                'base-url' => '/' . $this->unitedCMSManager->getOrganization() . '/',
                'content-label' => $settings->content_label ?? ucfirst($contentType->getTitle()) . '# {id}',
                'modal-html' => $this->templating->render(
                    $this->viewTypeManager->getViewType($view->getType())::getTemplate(),
                    [
                        'view' => $view,
                        'parameters' => $this->viewTypeManager->getTemplateRenderParameters($view, ViewTypeInterface::SELECT_MODE_SINGLE),
                    ]
                ),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0) {

        $name = ucfirst($this->field->getSettings()->content_type . 'Content');

        if($nestingLevel > 0) {
            $name .= 'Level' . $nestingLevel;
        }

        // We use the default content factory to build the type.
        return $schemaTypeManager->getSchemaType($name, $this->unitedCMSManager->getDomain(), $nestingLevel);
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0) {
        return $schemaTypeManager->getSchemaType('ReferenceFieldTypeInput', $this->unitedCMSManager->getDomain(), $nestingLevel);
    }

    /**
     * Resolve reference data. This means getting the referenced entity, checking access and returning it.
     *
     * @param array $value
     * @throws InvalidArgumentException
     * @return null|Content
     */
    function resolveGraphQLData($value) {
        if(empty($value)) {
            return null;
        }

        $contentType = $this->resolveContentType($value['domain'], $value['content_type']);

        // Find content for this content type.
        $content = $this->entityManager->getRepository('UnitedCMSCoreBundle:Content')->findOneBy(['contentType' => $contentType, 'id' => $value['content']]);
        if(!$content) {
            throw new InvalidArgumentException("No content with id '{$value['content']}' was found.");
        }

        // Check access to view content.
        if(!$this->authorizationChecker->isGranted(ContentVoter::VIEW, $content)) {
            throw new InvalidArgumentException("You are not allowed to view this content.");
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    function validateData($data): array {
        $violations = [];

        // Only validate available data.
        if(empty($data)) {
            return $violations;
        }

        // Make sure, that all required fields are set.
        if(empty($data['domain']) || empty($data['content_type']) || empty($data['content'])) {
            $violations[] = $this->createViolation('validation.missing_definition');
        }

        // Try to resolve the data to check if the current user is allowed to access it.
        else {
            try {
                $this->resolveGraphQLData($data);
            } catch (InvalidArgumentException $e) {
                $violations[] = $this->createViolation('validation.wrong_definition');
            }
        }

        return $violations;
    }
}