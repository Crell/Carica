<?php

declare(strict_types=1);


namespace Crell\Carica;

/**
 * Marker interface for user authentication attributes.
 *
 * A user authentication attribute should implement this
 * interface.  It may have whatever other attributes or
 * features are desired. Only one attribute with this
 * interface is allowed on a given route, but will indicate
 * to the appropriate middleware how a request should be
 * authenticated.
 */
interface UserAuthentication {}
