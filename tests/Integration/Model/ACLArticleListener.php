<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\Resource;
use MyCLabs\ACL\Model\Role;

class ACLArticleListener
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UnitOfWork
     */
    private $uow;

    /**
     * Stores the articles that are scheduled for insertion.
     *
     * @var Article[]
     */
    private $newArticles = [];

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->em = $eventArgs->getEntityManager();
        $this->uow = $this->em->getUnitOfWork();

        // Remember new articles
        $this->newArticles = [];
        foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Article) {
                $this->newArticles[] = $entity;
            }
        }

        // Process new roles and resources
        foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
            if ($this->supportsResource($entity)) {
                $this->processNewResource($entity);
            } elseif ($this->supportsRole($entity)) {
                $this->processNewRole($entity);
            }
        }

        $this->newArticles = [];
    }

    private function supportsResource($entity)
    {
        return $entity instanceof Article;
    }

    private function supportsRole($entity)
    {
        return $entity instanceof ArticleEditorRole
            || $entity instanceof AllArticlesEditorRole;
    }

    private function processNewRole(Role $role)
    {
        $editorActions = new Actions([Actions::VIEW, Actions::EDIT]);

        $authorizations = [];

        if ($role instanceof ArticleEditorRole) {
            $authorizations = [
                Authorization::create($role, $editorActions, Resource::fromEntity($role->getArticle())),
            ];
        } elseif ($role instanceof AllArticlesEditorRole) {
            $authorizations = [Authorization::create(
                $role,
                $editorActions,
                Resource::fromEntityClass('Tests\MyCLabs\ACL\Integration\Model\Article')
            )];
            $authorizations = array_merge($authorizations, $this->inherit($authorizations));
        }

        foreach ($authorizations as $authorization) {
            $this->persistNewEntity($authorization);
        }
    }

    private function processNewResource(Article $article)
    {
        $authorizations = [];

        // Inherits from the authorizations on "all articles"
        $repository = $this->em->getRepository('Tests\MyCLabs\ACL\Integration\Model\AllArticlesEditorRole');
        foreach ($repository->findAll() as $role) {
            /** @var AllArticlesEditorRole $role */
            foreach ($role->getRootAuthorizations() as $parentAuthorization) {
                $authorizations[] = Authorization::createChildAuthorization(
                    $parentAuthorization,
                    Resource::fromEntity($article)
                );
            }
        }

        foreach ($authorizations as $authorization) {
            $this->persistNewEntity($authorization);
        }
    }

    /**
     * @param Authorization[] $parentAuthorizations
     * @return Authorization[]
     */
    private function inherit(array $parentAuthorizations)
    {
        $authorizations = [];

        $articlesRepository = $this->em->getRepository('Tests\MyCLabs\ACL\Integration\Model\Article');
        $allArticles = array_merge($articlesRepository->findAll(), $this->newArticles);

        foreach ($parentAuthorizations as $parentAuthorization) {
            foreach ($allArticles as $article) {
                $authorizations[] = Authorization::createChildAuthorization(
                    $parentAuthorization,
                    Resource::fromEntity($article)
                );
            }
        }

        return $authorizations;
    }

    private function persistNewEntity($entity)
    {
        $this->em->persist($entity);
        $this->uow->computeChangeSet($this->em->getClassMetadata(get_class($entity)), $entity);
    }
}
