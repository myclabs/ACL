<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;

/**
 * Resource trait helper.
 *
 * This trait needs a $authorizations attribute.
 *
 * @property Authorization[]|Collection|Selectable $authorizations
 */
trait ResourceTrait
{
    /**
     * @return Authorization[]
     */
    public function getRootAuthorizations()
    {
        $criteria = new Criteria();
        $criteria->where($criteria->expr()->isNull('parentAuthorization'));

        return $this->authorizations->matching($criteria);
    }

    public function isAllowed(SecurityIdentityInterface $identity, $action)
    {
        $criteria = new Criteria();
        $criteria->where($criteria->expr()->eq('securityIdentity', $identity));
        $criteria->andWhere($criteria->expr()->eq('actions.' . $action, true));

        $authorizations = $this->authorizations->matching($criteria);

        return $authorizations->count() > 0;
    }

    public function addAuthorization(Authorization $authorization)
    {
        $this->authorizations[] = $authorization;
    }
}
