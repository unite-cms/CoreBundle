<?php

namespace UnitedCMS\CoreBundle\View;

use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use UnitedCMS\CoreBundle\Entity\View;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

class ViewTypeManager
{
    /**
     * @var ViewTypeInterface[]
     */
    private $viewTypes = [];

    /**
     * @var UrlGeneratorInterface $urlGenerator
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return ViewTypeInterface[]
     */
    public function getViewTypes(): array
    {
        return $this->viewTypes;
    }

    public function hasViewType($key): bool
    {
        return array_key_exists($key, $this->viewTypes);
    }

    public function getViewType($key): ViewTypeInterface
    {
        if (!$this->hasViewType($key)) {
            throw new \InvalidArgumentException("The view type: '$key' was not found.");
        }

        return $this->viewTypes[$key];
    }

    /**
     * Get template render parameters for the given view.
     * @param View $view
     * @param string $select_mode
     *
     * @return ViewParameterBag
     */
    public function getTemplateRenderParameters(View $view, $select_mode = ViewTypeInterface::SELECT_MODE_NONE): ViewParameterBag
    {
        $viewType = $this->getViewType($view->getType());
        $viewType->setEntity($view);
        $settings = $viewType->getTemplateRenderParameters($select_mode);
        $viewType->unsetEntity();

        return ViewParameterBag::createFromView($view, $this->urlGenerator, $select_mode, $settings ?? []);
    }

    /**
     * Validates view settings for given view by using the validation method of the view type.
     * @param View $view
     * @param ViewSettings $settings
     *
     * @return ConstraintViolation[]
     */
    public function validateViewSettings(View $view, ViewSettings $settings): array
    {
        $viewType = $this->getViewType($view->getType());
        $viewType->setEntity($view);
        $constraints = $viewType->validateSettings($settings);
        $viewType->unsetEntity();

        return $constraints;
    }

    /**
     * Returns all mutation schema types for the given view.
     * @param SchemaTypeManager $schemaTypeManager
     * @param View $view
     * @return array
     */
    public function getMutationSchemaTypes(SchemaTypeManager $schemaTypeManager, View $view) : array
    {
        $types = [];
        $viewType = $this->getViewType($view->getType());
        $viewType->setEntity($view);
        foreach($viewType->getMutationSchemaTypes($schemaTypeManager) as $action => $definition) {
            $types[strtolower($action) . ucfirst($view->getIdentifier()) . ucfirst($view->getContentType()->getIdentifier())] = $definition;
        }
        $viewType->unsetEntity();
        return $types;
    }

    /**
     * Resolves the value for a mutation action.
     *
     * @param View $view
     * @param $value
     * @param array $args
     * @param $context
     * @param ResolveInfo $info
     * @return mixed|null
     */
    public function resolveMutationSchemaType(View $view, $value, array $args, $context, ResolveInfo $info) {
        $nameParts = preg_split('/(?=[A-Z])/', $info->fieldName, -1, PREG_SPLIT_NO_EMPTY);

        if(count($nameParts) != 3) {
            return NULL;
        }
        if($nameParts[1] != ucfirst($view->getIdentifier()) || $nameParts[2] != ucfirst($view->getContentType()->getIdentifier())) {
            return NULL;
        }

        $viewType = $this->getViewType($view->getType());
        $viewType->setEntity($view);
        $resolvedValue = $viewType->resolveMutationSchemaType($nameParts[0], $value, $args, $context, $info);
        $viewType->unsetEntity();
        return $resolvedValue;
    }

    /**
     * @param ViewTypeInterface $viewType
     *
     * @return ViewTypeManager
     */
    public function registerViewType(ViewTypeInterface $viewType)
    {
        if (!isset($this->viewTypes[$viewType::getType()])) {
            $this->viewTypes[$viewType::getType()] = $viewType;
        }

        return $this;
    }
}