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
     * Checks if the identity is allowed to do the action on the resource.
     *
     * @param SecurityIdentityInterface $identity
     * @param string                    $action
     *
     * @return boolean Is allowed, or not.
     */
    public function isAllowed(SecurityIdentityInterface $identity, $action);

    /**
     * @param Authorization $authorization
     */
    public function addAuthorization(Authorization $authorization);

    /**
     * Returns the list of authorizations that apply to this resource, excluding inherited authorizations.
     *
     * Useful for resource inheritance, to cascade authorizations.
     *
     * @return Authorization[]
     */
    public function getRootAuthorizations();
}
