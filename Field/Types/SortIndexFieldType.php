<?php

namespace UnitedCMS\CoreBundle\Field\Types;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Field\FieldType;

class SortIndexFieldType extends FieldType
{
    const TYPE = "sortindex";
    const FORM_TYPE = IntegerType::class;

    public function onContentInsert(Content $content, EntityRepository $repository, LifecycleEventArgs $args) {

        // Set the position of the new item to max position.
        $data = $content->getData();
        $data[$this->field->getIdentifier()] = $repository->count(['contentType' => $content->getContentType()]);
        $content->setData($data);
    }

    public function onContentUpdate(Content $content, EntityRepository $repository, PreUpdateEventArgs $args) {

        $fieldIdentifier = $this->field->getIdentifier();

        // Get the old and new positions.
        $originalPosition = $args->getOldValue('data')[$fieldIdentifier];
        $updatedPosition = $content->getData()[$fieldIdentifier];

        // If we shift left, all items in between must be shifted right.
        if($originalPosition > $updatedPosition) {

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
        if($originalPosition < $updatedPosition) {

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