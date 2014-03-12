<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\Resource;
use MyCLabs\ACL\Model\Role;

/**
 * @ORM\Entity(readOnly=true)
 */
class AllArticlesEditorRole extends Role
{
    public function createAuthorizations(EntityManager $entityManager)
    {
        $rootAuthorization = Authorization::create(
            $this,
            new Actions([Actions::VIEW, Actions::EDIT]),
            Resource::fromEntityClass('Tests\MyCLabs\ACL\Integration\Model\Article')
        );

        // Cascade authorizations
        $articlesRepository = $entityManager->getRepository('Tests\MyCLabs\ACL\Integration\Model\Article');
        $authorizations = [$rootAuthorization];
        foreach ($articlesRepository->findAll() as $article) {
            $authorizations[] = $rootAuthorization->createChildAuthorization(Resource::fromEntity($article));
        }

        return $authorizations;
    }
}
