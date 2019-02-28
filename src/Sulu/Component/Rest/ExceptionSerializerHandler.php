<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use FOS\RestBundle\Serializer\Normalizer\ExceptionHandler;
use FOS\RestBundle\Util\ExceptionValueMap;
use JMS\Serializer\Context;

/**
 * Our handler for thrown exceptions in REST controllers.
 */
class ExceptionSerializerHandler extends ExceptionHandler
{
    /**
     * @var string
     */
    private $environment;

    public function __construct(ExceptionValueMap $messagesMap, $debug, string $environment)
    {
        parent::__construct($messagesMap, $debug);

        $this->environment = $environment;
    }

    protected function convertToArray(\Exception $exception, Context $context)
    {
        $data = parent::convertToArray($exception, $context);
        $data['code'] = $exception->getCode();
        if (in_array($this->environment, ['dev', 'test'])) {
            $data['errors'] = [(string) $exception];
        }

        return $data;
    }
}
