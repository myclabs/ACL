<?php

namespace MyCLabs\ACL\Model;

/**
 * ACL resource interface.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface ResourceInterface
{
    /**
     * @return mixed
     */
    public function getId();

    /**
     * Returns the list of authorizations that apply to this resource, excluding inherited authorizations.
     *
     * Useful for resource inheritance, to cascade authorizations.
     *
     * @return Authorization[]
     */
    public function getRootAuthorizations();
}
