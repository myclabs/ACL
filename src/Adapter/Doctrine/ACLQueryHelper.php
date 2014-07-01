<?php

namespace MyCLabs\ACL\Adapter\Doctrine;

use Doctrine\ORM\QueryBuilder;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\Identity;

/**
 * Helper for the Doctrine query builder to use ACL.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ACLQueryHelper
{
    /**
     * Joins with the authorizations and filters the results to keep only those authorized.
     *
     * @param QueryBuilder $qb
     * @param Identity     $identity
     * @param string       $action
     * @param string|null  $entityClass Class name of the entity that is the resource in the query.
     *                                  If omitted, it will be guessed from the SELECT.
     * @param string|null  $entityAlias Alias of the entity that is the resource in the query.
     *                                  If omitted, it will be guessed from the SELECT.
     *
     * @throws \RuntimeException The query builder has no "select" part
     */
    public static function joinACL(
        QueryBuilder $qb,
        Identity $identity,
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
            Authorization::class,
            'authorization',
            'WITH',
            $entityAlias . '.id = authorization.resource.id'
        );
        $qb->andWhere('authorization.resource.name = :acl_resource_name');
        $qb->andWhere('authorization.identity = :acl_identity');
        $qb->andWhere('authorization.actions.' . $action . ' = true');

        $qb->setParameter('acl_identity', $identity);
        $qb->setParameter('acl_resource_name', $entityClass);
    }
}
