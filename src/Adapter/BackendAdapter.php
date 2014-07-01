<?php

namespace MyCLabs\ACL\Adapter;

use MyCLabs\ACL\Model\Repository\AuthorizationRepository;
use MyCLabs\ACL\Model\Repository\RoleEntryRepository;

/**
 * Adapter for a backend.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface BackendAdapter
{
    /**
     * @return AuthorizationRepository
     */
    public function getAuthorizationRepository();

    /**
     * @return RoleEntryRepository
     */
    public function getRoleEntryRepository();
}
