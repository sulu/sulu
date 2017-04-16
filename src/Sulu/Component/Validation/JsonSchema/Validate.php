<?php

namespace Sulu\Component\Validation\JsonSchema;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Validate
{
    public $value;
    public $parameter;

    public function __construct(array $values)
    {
        $this->value = $values['value'];
        if (isset($values['param'])) {
            $this->parameter = $values['param'];
        }
    }
}
