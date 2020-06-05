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

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal the following class is only for internal use don't use it in your project
 */
class FlattenExceptionNormalizer implements NormalizerInterface
{
    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var string
     */
    private $environment;

    public function __construct(NormalizerInterface $normalizer, string $environment)
    {
        $this->normalizer = $normalizer;
        $this->environment = $environment;
    }

    public function normalize($exception, $format = null, array $context = [])
    {
        $data = $this->normalizer->normalize($exception, $format, $context);

        if (\is_array($data)) {
            $data['code'] = $exception->getCode();
        }

        if (\in_array($this->environment, ['dev', 'test'])) {
            $errors = '';

            if ($exception instanceof FlattenException) {
                $errors .= $exception->getClass();
                $errors .= ': ';
                $errors .= $exception->getMessage();
                $errors .= ' in ';
                $errors .= $exception->getFile();
                $errors .= ':';
                $errors .= $exception->getLine();
                $errors .= \PHP_EOL . 'Stack trace:' . \PHP_EOL;
                $errors .= $exception->getTraceAsString();
            } else {
                $errors = (string) $exception;
            }

            $data['errors'] = [$errors];
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $this->normalizer->supportsNormalization($data, $format);
    }
}
