<?php

namespace Tests\MyCLabs\ACL\Integration\Issues\Issue10;

use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\ResourceGraph\ResourceGraphTraverser;

class ProjectGraphTraverser implements ResourceGraphTraverser
{
    public function getAllParentResources(ResourceInterface $project)
    {
        if (! $project instanceof Project) {
            throw new \RuntimeException;
        }

        return [ $project->getAccount() ];
    }

    public function getAllSubResources(ResourceInterface $project)
    {
        if (! $project instanceof Project) {
            throw new \RuntimeException;
        }

        return $project->getItems();
    }
}
