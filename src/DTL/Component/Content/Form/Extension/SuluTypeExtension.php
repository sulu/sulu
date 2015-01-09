<?php

namespace DTL\Component\Content\Form\Extension;

use Symfony\Component\Form\AbstractExtension;

class SuluTypeExtension extends AbstractExtension
{
    protected function loadTypes()
    {
        return array(
            new Type\BlockType(),
            new Type\SmartContentType(),
        );
    }
}
