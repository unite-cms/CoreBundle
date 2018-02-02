<?php

namespace UnitedCMS\CoreBundle\Tests\Field;


use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\ContentTypeField;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;

class SortIndexFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings() {

        // Empty settings can be valid.
        $ctField = $this->createContentTypeField('sortindex');
        $this->assertCount(0, $this->container->get('validator')->validate($ctField));
    }

    public function testAutoUpdateSortIndexOnInsertUpdateDelete() {

        $contentType = new ContentType();
        $contentType->setTitle('ct')
            ->setIdentifier('ct')
            ->setDomain(new Domain())
            ->getDomain()
            ->setTitle('D1')
            ->setIdentifier('d1')
            ->setOrganization(new Organization())
            ->getOrganization()
            ->setTitle('O1')
            ->setIdentifier('o1');

        $field = new ContentTypeField();
        $field->setType('sortindex')->setIdentifier('position')->setTitle('Position');
        $contentType->addField($field);

        $field = new ContentTypeField();
        $field->setType('text')->setIdentifier('label')->setTitle('Label');
        $contentType->addField($field);

        $this->em->persist($contentType->getDomain()->getOrganization());
        $this->em->persist($contentType->getDomain());
        $this->em->persist($contentType);
        $this->em->flush();

        // Create content for this content type.
        $content = [];
        for($i = 0; $i < 4; $i++) {
            $content['C' . ($i + 1)] = new Content();
            $content['C' . ($i + 1)]
                ->setData(['position' => 0, 'label' => 'C' . ($i + 1)])
                ->setContentType($contentType);
            $this->em->persist($content['C' . ($i + 1)]);
            $this->em->flush();
        }

        // Make sure, that content got an auto incremented position.
        $getContent = $this->em->getRepository('UnitedCMSCoreBundle:Content')->createQueryBuilder('c')
            ->select('c')
            ->where('c.contentType = :contentType')
            ->orderBy("JSON_EXTRACT(c.data, '$.position')", 'ASC')
            ->getQuery()->execute([':contentType' => $contentType]);

        $this->assertEquals(0, $getContent[0]->getData()['position']);
        $this->assertEquals(1, $getContent[1]->getData()['position']);
        $this->assertEquals(2, $getContent[2]->getData()['position']);
        $this->assertEquals(3, $getContent[3]->getData()['position']);

        // Now move the first element to the last position.
        $data = $content['C1']->getData();
        $data['position'] = 3;
        $content['C1']->setData($data);
        $this->em->flush($content['C1']);
        $this->em->clear();

        // Make sure, that the content is in correct order.
        $getContent = $this->em->getRepository('UnitedCMSCoreBundle:Content')->createQueryBuilder('c')
            ->select('c')
            ->where('c.contentType = :contentType')->setParameter(':contentType', $contentType)
            ->addOrderBy("JSON_EXTRACT(c.data, '$.position')")
            ->getQuery()->execute();

        $this->assertEquals(['position' => 0, 'label' => 'C2'], $getContent[0]->getData());
        $this->assertEquals(['position' => 1, 'label' => 'C3'], $getContent[1]->getData());
        $this->assertEquals(['position' => 2, 'label' => 'C4'], $getContent[2]->getData());
        $this->assertEquals(['position' => 3, 'label' => 'C1'], $getContent[3]->getData());


        // Now move the 3rd element to the first position.
        $getContent[1]->setData(['position' => 0, 'label' => 'C3']);
        $this->em->flush($getContent[1]);
        $this->em->clear();

        // Make sure, that the content is in correct order.
        $getContent = $this->em->getRepository('UnitedCMSCoreBundle:Content')->createQueryBuilder('c')
            ->select('c')
            ->where('c.contentType = :contentType')->setParameter(':contentType', $contentType)
            ->addOrderBy("JSON_EXTRACT(c.data, '$.position')")
            ->getQuery()->execute();

        $this->assertEquals(['position' => 0, 'label' => 'C3'], $getContent[0]->getData());
        $this->assertEquals(['position' => 1, 'label' => 'C2'], $getContent[1]->getData());
        $this->assertEquals(['position' => 2, 'label' => 'C4'], $getContent[2]->getData());
        $this->assertEquals(['position' => 3, 'label' => 'C1'], $getContent[3]->getData());

        // Now move C1 to position 1.
        $getContent[3]->setData(['position' => 1, 'label' => 'C1']);
        $this->em->flush($getContent[3]);
        $this->em->clear();

        // Make sure, that the content is in correct order.
        $getContent = $this->em->getRepository('UnitedCMSCoreBundle:Content')->createQueryBuilder('c')
            ->select('c')
            ->where('c.contentType = :contentType')->setParameter(':contentType', $contentType)
            ->addOrderBy("JSON_EXTRACT(c.data, '$.position')")
            ->getQuery()->execute();

        $this->assertEquals(['position' => 0, 'label' => 'C3'], $getContent[0]->getData());
        $this->assertEquals(['position' => 1, 'label' => 'C1'], $getContent[1]->getData());
        $this->assertEquals(['position' => 2, 'label' => 'C2'], $getContent[2]->getData());
        $this->assertEquals(['position' => 3, 'label' => 'C4'], $getContent[3]->getData());

        // Now delete one content element, all elements after should auto update.
        $this->em->remove($getContent[1]);
        $this->em->flush($getContent[1]);
        $this->em->clear();

        $getContent = $this->em->getRepository('UnitedCMSCoreBundle:Content')->createQueryBuilder('c')
            ->select('c')
            ->where('c.contentType = :contentType')->setParameter(':contentType', $contentType)
            ->addOrderBy("JSON_EXTRACT(c.data, '$.position')")
            ->getQuery()->execute();

        $this->assertEquals(['position' => 0, 'label' => 'C3'], $getContent[0]->getData());
        $this->assertEquals(['position' => 1, 'label' => 'C2'], $getContent[1]->getData());
        $this->assertEquals(['position' => 2, 'label' => 'C4'], $getContent[2]->getData());
    }


}