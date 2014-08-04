<?php

namespace vendor\sulu\sulu\tests\Sulu\Component\Content;

use Sulu\Component\Content\ContentContext;

class ContentContextTest extends \PHPUnit_Framework_TestCase
{
    public function testContentContext()
    {
        $contentContext = new ContentContext(
            'languageDefault',
            'templateDefault',
            'propertyPrefix',
            'languageNamespace'
        );

        $this->assertEquals('languageDefault', $contentContext->getLanguageDefault());
        $this->assertEquals('templateDefault', $contentContext->getTemplateDefault());
        $this->assertEquals('propertyPrefix', $contentContext->getPropertyPrefix());
        $this->assertEquals('languageNamespace', $contentContext->getLanguageNamespace());
    }
}
