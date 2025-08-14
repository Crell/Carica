<?php

declare(strict_types=1);


namespace Crell\Carica;

/**
 * Marker interface for user authorization attributes.
 *
 * A user authorization attribute should implement this
 * interface.  It may have whatever other attributes or
 * features are desired. Only one attribute with this
 * interface is allowed on a given route, but will indicate
 * to the appropriate middleware how a request should be
 * authorized.
 */
interface UserAuthorization {}
