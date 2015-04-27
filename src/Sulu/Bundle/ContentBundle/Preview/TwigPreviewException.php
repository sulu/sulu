<?php


namespace Sulu\Bundle\ContentBundle\Preview;

/**
 * Twig exception in preview
 */
class TwigPreviewException extends PreviewException
{
    const EXCEPTION_CODE = 3003;

    /**
     * TwigPreviewException constructor.
     *
     * @param \Exception $previous
     */
    public function __construct(\Exception $previous)
    {
        parent::__construct(
            $previous->getMessage(),
            self::EXCEPTION_CODE,
            $previous
        );
    }
}
