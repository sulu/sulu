<?php

namespace Sulu\Component\Content\Mapper;

class ContentMapperSuluTest extends SuluTestCase
{
    public function setUp()
    {
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->init
    }
}
