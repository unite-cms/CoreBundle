<?php


namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\Common\Collections\Expr\Comparison;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Query\DataFieldComparison;

class ReferenceOfType extends AbstractFieldType
{
    const TYPE = 'referenceOf';
    const GRAPHQL_INPUT_TYPE = null;

    /**
     * @var \UniteCMS\CoreBundle\Domain\DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(DomainManager $domainManager, SaveExpressionLanguage $saveExpressionLanguage)
    {
        $this->domainManager = $domainManager;
        parent::__construct($saveExpressionLanguage);
    }

    /**
     * {@inheritDoc}
     */
    public function validateFieldDefinition(ContentType $contentType, ContentTypeField $field, ExecutionContextInterface $context) : void {

        // Validate return type.
        $referencedContentType = $this->domainManager->current()->getContentTypeManager()->getContentType($field->getReturnType());

        if(!$field->isListOf()) {
            $context
                ->buildViolation('The return type of a reference_of field must always be a list.')
                ->addViolation();
            return;
        }

        if(!$field->getSettings()->has('reference_field')) {
            $context
                ->buildViolation('Please set the "reference_field" field of type "{{ type }}".')
                ->setParameter('{{ type }}', static::getType())
                ->addViolation();
            return;
        }

        if(!$referencedContentType) {
            $context
                ->buildViolation('Invalid GraphQL return type "{{ return_type }}" for field of type "{{ type }}". Please use a GraphQL type implements UniteContent and have a reference to this field.')
                ->setParameter('{{ type }}', static::getType())
                ->setParameter('{{ return_type }}', $field->getReturnType())
                ->addViolation();
            return;
        }

        $field = $referencedContentType->getField($field->getSettings()->get('reference_field'));

        // TODO: With this check, union types are not supported. We could improve this in the future.
        if($field->getReturnType() !== $contentType->getId()) {
            $context
                ->buildViolation('The GraphQL return type of the configured reference_field must be "{{ content_type }}", but it is "{{ return_type }}".')
                ->setParameter('{{ content_type }}', $contentType->getId())
                ->setParameter('{{ return_type }}', $field->getReturnType())
                ->addViolation();
            return;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function resolveField(ContentInterface $content, ContentTypeField $field, FieldData $fieldData) {

        if(empty($content->getId())) {
            return [];
        }

        $domain = $this->domainManager->current();
        $contentManager = $domain->getContentManager();

        $referencedContentType = $this->domainManager->current()->getContentTypeManager()->getContentType($field->getReturnType());
        $reference_field = $referencedContentType->getField($field->getSettings()->get('reference_field'));

        // Find all content that is referencing this objects.
        $criteria = new ContentCriteria();
        $criteria->where(new DataFieldComparison(
            $reference_field->getId(),
            Comparison::EQ,
            $content->getId()
        ));

        return $contentManager->find($domain, $field->getReturnType(), $criteria)->getResult();
    }
}
