<?php

namespace MyCLabs\ACL;

use Doctrine\ORM\QueryBuilder;
use MyCLabs\ACL\Model\SecurityIdentityInterface;

/**
 * Helper for the query builder to use ACL.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class QueryBuilderHelper
{
    /**
     * Joins with the authorizations and filters the results to keep only those authorized.
     *
     * @param QueryBuilder              $qb
     * @param string                    $resourceAlias Alias of the class that is the resource in the query.
     * @param SecurityIdentityInterface $identity
     * @param string                    $action
     */
    public static function joinACL(
        QueryBuilder $qb,
        $resourceAlias,
        SecurityIdentityInterface $identity,
        $action
    ) {
        $qb->innerJoin($resourceAlias . '.authorizations', 'authorization');
        $qb->andWhere('authorization.securityIdentity = :acl_identity');
        $qb->andWhere('authorization.actions.' . $action . ' = true');

        $qb->setParameter('acl_identity', $identity);
    }
}
