<?php

namespace UniteCMS\CoreBundle\Tests\Mock;

use Doctrine\Common\Collections\Expr\Comparison;
use UniteCMS\CoreBundle\Content\ContentFilterInput;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Query\BaseFieldComparison;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Security\User\UserInterface;
use UniteCMS\CoreBundle\Security\User\UserManagerInterface;

class TestUserManager extends TestContentManager implements UserManagerInterface {

    public function create(Domain $domain, string $type): ContentInterface {
        return new TestUser($type);
    }

    public function findByUsername(Domain $domain, string $type, string $username): ?UserInterface {
        $criteria = new ContentCriteria();
        $criteria->where(new BaseFieldComparison(
            'username',
            Comparison::EQ,
            $username
        ));
        
        $result = $this->find($domain, $type, $criteria);
        $results = $result->getResult();
        return $result->getTotal() > 0 ? reset($results) : null;
    }
}
