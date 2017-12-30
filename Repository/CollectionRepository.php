<?php

namespace UnitedCMS\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CollectionRepository
 */
class CollectionRepository extends EntityRepository
{
    public function findByIdentifiers($organization, $domain, $contentType, $collection)
    {
        $result = $this->createQueryBuilder('c')
            ->select('c', 'ct', 'dm', 'org')
            ->join('c.contentType', 'ct')
            ->join('ct.domain', 'dm')
            ->join('dm.organization', 'org')
            ->where('org.identifier = :organization')
            ->andWhere('dm.identifier = :domain')
            ->andWhere('ct.identifier = :contentType')
            ->andWhere('c.identifier = :collection')
            ->setParameters(
                [
                    'organization' => $organization,
                    'domain' => $domain,
                    'contentType' => $contentType,
                    'collection' => $collection,
                ]
            )
            ->getQuery()->getResult();

        return (count($result) > 0) ? $result[0] : null;
    }
}
