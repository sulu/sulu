<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Domain\Exception;

/**
 * Thrown when a workflow transition cannot be authorized because the security context behind the
 * resource cannot be determined. That is a configuration error, not a decision about the caller:
 * answering "denied" would break every existing custom content type on upgrade and answering
 * "allowed" would publish without a check, so it is reported instead.
 */
class UnresolvableSecurityContextException extends \RuntimeException
{
}
