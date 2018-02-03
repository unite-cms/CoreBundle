<?php

namespace UnitedCMS\CoreBundle\Field\Types;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Field\FieldType;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

class SortIndexFieldType extends FieldType
{
    const TYPE = "sortindex";
    const FORM_TYPE = IntegerType::class;

    function getGraphQLType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return Type::int();
    }

    function getGraphQLInputType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0) {
        return Type::int();
    }

    public function onContentInsert(Content $content, EntityRepository $repository, LifecycleEventArgs $args) {

        // Set the position of the new item to max position.
        $data = $content->getData();
        $data[$this->field->getIdentifier()] = $repository->count(['contentType' => $content->getContentType()]);
        $content->setData($data);
    }

    public function onContentUpdate(Content $content, EntityRepository $repository, PreUpdateEventArgs $args) {

        $fieldIdentifier = $this->field->getIdentifier();

        // if we recover a deleted content, it's like we are moving the item from the end of the list to its original position.
        $originalPosition = null;

        // Get the old position, if available.

        if($args->hasChangedField('data')) {
            $originalPosition = $args->getOldValue('data')[$fieldIdentifier];
        }

        // Get new position.
        $updatedPosition = $content->getData()[$fieldIdentifier];

        // If we shift left, all items in between must be shifted right.
        if($originalPosition !== null && $originalPosition > $updatedPosition) {

            $repository->createQueryBuilder('c')
                ->update('UnitedCMSCoreBundle:Content', 'c')
                ->set('c.data', "JSON_SET(c.data, '$.$fieldIdentifier', CAST(JSON_EXTRACT(c.data, '$.$fieldIdentifier') +1 AS int))")
                ->where('c.contentType = :contentType')
                ->andWhere("JSON_EXTRACT(c.data, '$.$fieldIdentifier') BETWEEN :first AND :last")
                ->setParameters([
                    ':contentType' => $content->getContentType(),
                    ':first' => $updatedPosition,
                    ':last' => $originalPosition - 1,
                ])
                ->getQuery()->execute();

        }

        // if we shift right, all items in between must be shifted left.
        if($originalPosition !== null && $originalPosition < $updatedPosition) {

            $repository->createQueryBuilder('c')
                ->update('UnitedCMSCoreBundle:Content', 'c')
                ->set('c.data', "JSON_SET(c.data, '$.$fieldIdentifier', CAST(JSON_EXTRACT(c.data, '$.$fieldIdentifier') -1 AS int))")
                ->where('c.contentType = :contentType')
                ->andWhere("JSON_EXTRACT(c.data, '$.$fieldIdentifier') BETWEEN :first AND :last")
                ->setParameters([
                    ':contentType' => $content->getContentType(),
                    ':first' => $originalPosition + 1,
                    ':last' => $updatedPosition,
                ])
                ->getQuery()->execute();
        }

        // If we have no originalPosition, for example if we recover a deleted content.
        if($originalPosition === null) {

            $repository->createQueryBuilder('c')
                ->update('UnitedCMSCoreBundle:Content', 'c')
                ->set('c.data', "JSON_SET(c.data, '$.$fieldIdentifier', CAST(JSON_EXTRACT(c.data, '$.$fieldIdentifier') +1 AS int))")
                ->where('c.contentType = :contentType')
                ->andWhere("JSON_EXTRACT(c.data, '$.$fieldIdentifier') >= :first")
                ->setParameters([
                    ':contentType' => $content->getContentType(),
                    ':first' => $updatedPosition,
                ])
                ->getQuery()->execute();
        }
    }

    public function onContentRemove(Content $content, EntityRepository $repository, LifecycleEventArgs $args) {

        $fieldIdentifier = $this->field->getIdentifier();

        // all content after the deleted one should get --.
        $repository->createQueryBuilder('c')
            ->update('UnitedCMSCoreBundle:Content', 'c')
            ->set('c.data', "JSON_SET(c.data, '$.$fieldIdentifier', CAST(JSON_EXTRACT(c.data, '$.$fieldIdentifier') -1 AS int))")
            ->where('c.contentType = :contentType')
            ->andWhere("JSON_EXTRACT(c.data, '$.$fieldIdentifier') > :last")
            ->setParameters([
                ':contentType' => $content->getContentType(),
                ':last' => $content->getData()[$fieldIdentifier],
            ])
            ->getQuery()->execute();
    }
}