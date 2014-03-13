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
     * @param string                    $entityClass Class name of the entity that is the resource in the query.
     * @param string                    $entityAlias Alias of the entity that is the resource in the query.
     * @param SecurityIdentityInterface $identity
     * @param string                    $action
     */
    public static function joinACL(
        QueryBuilder $qb,
        $entityClass,
        $entityAlias,
        SecurityIdentityInterface $identity,
        $action
    ) {

        $qb->innerJoin(
            'MyCLabs\ACL\Model\Authorization',
            'authorization',
            'WITH',
            $entityAlias . '.id = authorization.entityId'
        );
        $qb->andWhere('authorization.entityClass = :acl_entity_class');
        $qb->andWhere('authorization.entityField IS NULL');
        $qb->andWhere('authorization.securityIdentity = :acl_identity');
        $qb->andWhere('authorization.actions.' . $action . ' = true');

        $qb->setParameter('acl_identity', $identity);
        $qb->setParameter('acl_entity_class', $entityClass);
    }
}
