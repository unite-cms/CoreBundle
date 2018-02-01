<?php

namespace UnitedCMS\CoreBundle\Field\Types;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UnitedCMS\CoreBundle\View\ViewTypeInterface;
use UnitedCMS\CoreBundle\View\ViewTypeManager;
use UnitedCMS\CoreBundle\Entity\View;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Field\FieldType;
use UnitedCMS\CoreBundle\Form\WebComponentType;
use UnitedCMS\CoreBundle\Security\ContentVoter;
use UnitedCMS\CoreBundle\Security\DomainVoter;
use UnitedCMS\CoreBundle\Service\UnitedCMSManager;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

class ReferenceFieldType extends FieldType
{
    const TYPE                      = "reference";
    const FORM_TYPE                 = WebComponentType::class;
    const SETTINGS                  = ['domain', 'content_type', 'view', 'content_label'];
    const REQUIRED_SETTINGS         = ['domain', 'content_type'];

    private $validator;
    private $authorizationChecker;
    private $unitedCMSManager;
    private $viewTypeManager;
    private $entityManager;
    private $templating;

    function __construct(ValidatorInterface $validator, AuthorizationChecker $authorizationChecker, UnitedCMSManager $unitedCMSManager, EntityManager $entityManager, ViewTypeManager $viewTypeManager, TwigEngine $templating)
    {
        $this->validator = $validator;
        $this->authorizationChecker = $authorizationChecker;
        $this->unitedCMSManager = $unitedCMSManager;
        $this->viewTypeManager = $viewTypeManager;
        $this->entityManager = $entityManager;
        $this->templating = $templating;
    }

    function getFormOptions(): array
    {
        $settings = $this->field->getSettings();
        $settings->view = $settings->view ?? 'all';

        $organization = $this->unitedCMSManager->getOrganization();

        $domain = $organization->getDomains()->filter(function( Domain $domain ) use($settings) { return $domain->getIdentifier() == $settings->domain; })->first();

        if(!$domain) {
            throw new InvalidArgumentException("No domain with identifier '{$settings->domain}' was found in this organization.");
        }

        if(!$this->authorizationChecker->isGranted(DomainVoter::VIEW, $domain)) {
            throw new InvalidArgumentException("You are not allowed to view this domain.");
        }

        $contentType = $domain->getContentTypes()->filter(function( ContentType $contentType ) use($settings) { return $contentType->getIdentifier() == $settings->content_type; })->first();

        if(!$contentType) {
            throw new InvalidArgumentException("No content_Type with identifier '{$settings->content_type}' was found for this organization and domain.");
        }

        if(!$this->authorizationChecker->isGranted(ContentVoter::LIST, $contentType)) {
            throw new InvalidArgumentException("You are not allowed to view this content_type.");
        }

        $view = $contentType->getViews()->filter(function( View $view) use($settings) { return $view->getIdentifier() == $settings->view; })->first();

        if(!$view) {
            throw new InvalidArgumentException("No view with identifier '{$settings->view}' was found for this organization, domain and content type.");
        }

        // Reload the full view object
        $view = $this->entityManager->getRepository('UnitedCMSCoreBundle:View')->findOneBy([
            'contentType' => $contentType,
            'id' => $view->getId(),
        ]);

        $settings->content_label = $settings->content_label ?? ucfirst($contentType->getTitle()) . '# {id}';

        return array_merge(parent::getFormOptions(), [
            'tag' => 'united-cms-core-reference-field',
            'empty_data' => [
                'domain' => $domain->getIdentifier(),
                'content_type' => $contentType->getIdentifier(),
            ],
            'attr' => [
                'base-url' => '/' . $this->unitedCMSManager->getOrganization() . '/',
                'content-label' => $settings->content_label,
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

    function getDataTransformer() {
        return new class implements DataTransformerInterface {
            public function transform($value)
            {
                return $value;
            }

            public function reverseTransform($value)
            {
                if(empty($value) || empty($value['content'])) {
                    return null;
                }

                return $value;
            }
        };
    }

    function getGraphQLType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        $name = ucfirst($this->field->getSettings()->content_type . 'Content');
        if($nestingLevel > 0) {
            $name .= 'Level' . $nestingLevel;
        }

        // We use the default content in view factory to build the type.
        return $schemaTypeManager->getSchemaType($name, $this->unitedCMSManager->getDomain(), $nestingLevel);
    }

    function getGraphQLInputType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return $schemaTypeManager->getSchemaType('ReferenceFieldTypeInput', $this->unitedCMSManager->getDomain(), $nestingLevel);
    }

    function resolveGraphQLData($value)
    {
        if(empty($value)) {
            return null;
        }

        $organization = $this->unitedCMSManager->getOrganization();

        $domain = $organization->getDomains()->filter(function( Domain $domain ) use($value) { return $domain->getIdentifier() == $value['domain']; })->first();

        if(!$domain) {
            throw new InvalidArgumentException("No domain with identifier '{$value['domain']}' was found in this organization.");
        }

        if(!$this->authorizationChecker->isGranted(DomainVoter::VIEW, $domain)) {
            throw new InvalidArgumentException("You are not allowed to view this domain.");
        }

        $contentType = $domain->getContentTypes()->filter(function( ContentType $contentType ) use($value) { return $contentType->getIdentifier() == $value['content_type']; })->first();

        if(!$contentType) {
            throw new InvalidArgumentException("No content_Type with identifier '{$value['content_type']}' was found for this organization and domain.");
        }

        $content = $this->entityManager->getRepository('UnitedCMSCoreBundle:Content')->findOneBy([
            'contentType' => $contentType,
            'id' => $value['content'],
        ]);

        if(!$content) {
            throw new InvalidArgumentException("No content with id '{$value['content']}' was found.");
        }

        if(!$this->authorizationChecker->isGranted(ContentVoter::VIEW, $content)) {
            throw new InvalidArgumentException("You are not allowed to view this content.");
        }

        return $content;
    }

    function validateData($data): array
    {
        $violations = [];

        if(empty($data)) {
            return $violations;
        }

        if(empty($data['domain']) || empty($data['content_type']) || empty($data['content'])) {
            $violations[] = new ConstraintViolation(
                'validation.missing_definition',
                'validation.missing_definition',
                [],
                null,
                '[' . $this->getIdentifier() . ']',
                $data
            );
        }

        // Try to resolve the data to check if the current user is allowed to access it.
        else {
            try {
                $this->resolveGraphQLData($data);
            } catch (InvalidArgumentException $e) {
                $violations[] = new ConstraintViolation(
                    'validation.wrong_definition',
                    'validation.wrong_definition',
                    [],
                    null,
                    '[' . $this->getIdentifier() . ']',
                    $data
                );
            }
        }

        return $violations;
    }
}