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

use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @var bool
     */
    private $debug;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(NormalizerInterface $normalizer, string $debug, TranslatorInterface $translator)
    {
        $this->normalizer = $normalizer;
        $this->debug = $debug;
        $this->translator = $translator;
    }

    public function normalize($exception, $format = null, array $context = [])
    {
        $data = $this->normalizer->normalize($exception, $format, $context);
        $data['code'] = $exception->getCode();

        $contextException = $context['exception'] ?? null;
        if ($contextException instanceof TranslationErrorMessageExceptionInterface) {
            // set error message to detail property of response to match rfc 7807
            $data['detail'] = $this->translator->trans(
                $contextException->getMessageTranslationKey(),
                $contextException->getMessageTranslationParameters(),
                'admin'
            );
        }

        if ($this->debug) {
            if ($exception instanceof FlattenException) {
                $errors = $exception->getAsString();
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
