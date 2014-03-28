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
     * @param SecurityIdentityInterface $identity
     * @param string                    $action
     * @param string|null               $entityClass Class name of the entity that is the resource in the query.
     *                                               If omitted, it will be guessed from the SELECT.
     * @param string|null               $entityAlias Alias of the entity that is the resource in the query.
     *                                               If omitted, it will be guessed from the SELECT.
     *
     * @throws \RuntimeException The query builder has no "select" part
     */
    public static function joinACL(
        QueryBuilder $qb,
        SecurityIdentityInterface $identity,
        $action,
        $entityClass = null,
        $entityAlias = null
    ) {
        if ($entityClass === null) {
            $rootEntities = $qb->getRootEntities();
            if (! isset($rootEntities[0])) {
                throw new \RuntimeException('The query builder has no "select" part');
            }
            $entityClass = $rootEntities[0];
        }
        if ($entityAlias === null) {
            $rootAliases = $qb->getRootAliases();
            if (! isset($rootAliases[0])) {
                throw new \RuntimeException('The query builder has no "select" part');
            }
            $entityAlias = $rootAliases[0];
        }

        $qb->innerJoin(
            'MyCLabs\ACL\Model\Authorization',
            'authorization',
            'WITH',
            $entityAlias . '.id = authorization.entityId'
        );
        $qb->andWhere('authorization.entityClass = :acl_entity_class');
        $qb->andWhere('authorization.securityIdentity = :acl_identity');
        $qb->andWhere('authorization.actions.' . $action . ' = true');

        $qb->setParameter('acl_identity', $identity);
        $qb->setParameter('acl_entity_class', $entityClass);
    }
}
