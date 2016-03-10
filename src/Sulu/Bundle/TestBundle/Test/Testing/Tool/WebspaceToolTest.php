<?php

namespace Sulu\Bundle\TestBundle\Test\Testing\Tool;

use Sulu\Bundle\TestBundle\Testing\Tool\WebspaceTool;

class WebspaceToolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * It should generate a webspace document.
     */
    public function testGenerate()
    {
        $dom = WebspaceTool::generateWebspace([
            'localizations' => [ 'de' => []],
            'navigation' => [
                'main' => [],
            ],
            'portals' => [
                'test' => []
            ],
        ]);
        $dom->formatOutput = true;
        $this->assertEquals(<<<'EOT'
<?xml version="1.0"?>
<webspace>
  <name>Sulu CMF</name>
  <key>sulu_io</key>
  <localizations>
    <localization language="de" country="en"/>
  </localizations>
  <theme>
    <key>default</key>
    <default-templates>
      <default-template type="page">default</default-template>
      <default-template type="homepage">default</default-template>
    </default-templates>
  </theme>
  <navigation>
    <contexts>
      <context key="main">
        <meta>
          <title lang="en">Main Navigation</title>
        </meta>
      </context>
    </contexts>
  </navigation>
  <portals>
    <portal>
      <name>Sulu CMF</name>
      <key>test</key>
      <localizations>
        <localization language="de" country="at" default="1"/>
      </localizations>
      <environments>
        <environment type="prod">
          <urls>
            <url language="de" country="at"/>
            <url language="de" country="at"/>
            <url language="" country="" redirect="sulu.lo"/>
          </urls>
        </environment>
        <environment type="dev">
          <urls>
            <url language="de" country="at"/>
            <url language="de" country="at"/>
            <url language="" country="" redirect="sulu.lo"/>
          </urls>
        </environment>
        <environment type="test">
          <urls>
            <url language="de" country="at"/>
            <url language="de" country="at"/>
            <url language="" country="" redirect="sulu.lo"/>
          </urls>
        </environment>
      </environments>
    </portal>
  </portals>
</webspace>

EOT
        , $dom->saveXml());
    }
}
