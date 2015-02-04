<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Command;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateSitemapCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    public function setUp()
    {
        $application = new Application($this->getContainer()->get('kernel'));

        $sitemapGeneratorCommand = new SitemapGeneratorCommand();
        $sitemapGeneratorCommand->setApplication($application);
        $this->tester = new CommandTester($sitemapGeneratorCommand);
    }

    public function testExecute()
    {
        $this->tester->execute(
            array(
                'webspace' => 'sulu_io',
                'portal' => 'sulu_io',
            )
        );

        $this->assertContains(sprintf('Done: Generated "%s"', 'sulu_io'), $this->tester->getDisplay());
    }
}
