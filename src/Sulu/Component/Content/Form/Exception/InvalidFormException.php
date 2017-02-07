<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Form\Exception;

use Symfony\Component\Form\FormInterface;

/** This exception class should be used when a Form fails validation.  The form
 * should be passed as the constructor - a useful error message will follow.
 */
class InvalidFormException extends \Exception
{
    public function __construct(FormInterface $form)
    {
        $message = [];

        foreach ($form->getErrors(true, true) as $error) {
            $message[] = sprintf(
                '[%s] %s (%s)',
                $error->getOrigin() ? $error->getOrigin()->getPropertyPath() : '-',
                $error->getMessage(),
                json_encode($error->getMessageParameters())
            );
        }

        parent::__construct(implode("\n", $message));
    }
}
