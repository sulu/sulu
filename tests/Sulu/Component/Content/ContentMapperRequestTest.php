<?php

namespace Sulu\Component\Content;

class ContentMapperRequestTest extends \PHPUnit_Framework_TestCase
{
    public function requestTest()
    {
        $request = ContentMapperRequest::create('page');

        foreach (array(
            'data' => 'Foobar data',
            'templateKey' => 'template_key',
            'webspaceKey' => 'webspace_key',
            'languageCode' => 'language_code',
            'userId' => 5,
            'partialUpdate' => true,
            'uuid' => '1234',
            'parentUuid' => '4321',
            'state' => 2,
            'isShadow' => true,
            'shadowBaseLanguage' => 'de'
            ) as $key => $value) 
        {
            $request->{'set' . ucfirst($key)}($value);
        }
    }

}
