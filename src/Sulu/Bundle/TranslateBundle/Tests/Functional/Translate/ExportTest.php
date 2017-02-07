<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Tests\Functional\Translate;

use Prophecy\Argument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TranslateBundle\Translate\Export;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Translator;

class ExportTest extends SuluTestCase
{
    private static $fixturePath;

    /**
     * @var Export
     */
    protected $export;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$fixturePath = __DIR__ . '/../../DataFixtures/Files';
    }

    public function setUp()
    {
        $output = $this->prophesize(OutputInterface::class);

        $translator = $this->prophesize(Translator::class);
        $translator->getCatalogue(Argument::any())
            ->willReturn(
                new MessageCatalogue(
                    'en',
                    [
                        'backend' => [
                            'key1' => 'translation1',
                            'key2' => 'translation2',
                            'key3' => 'translation3',
                            'key4' => 'translation4',
                        ],
                        'frontend' => [
                            'key5' => 'translation5',
                            'key6' => 'translation6',
                            'key7' => 'translation7',
                            'key8' => 'translation8',
                        ],
                    ]
                )
            );

        $this->export = new Export($translator->reveal(), $output->reveal());
    }

    public function tearDown()
    {
        if (file_exists(self::$fixturePath . '/sulu-test.en.xlf')) {
            unlink(self::$fixturePath . '/sulu-test.en.xlf');
        }
        if (file_exists(self::$fixturePath . '/sulu-test.frontend.en.xlf')) {
            unlink(self::$fixturePath . '/sulu-test.frontend.en.xlf');
        }
        if (file_exists(self::$fixturePath . '/sulu-test.en.json')) {
            unlink(self::$fixturePath . '/sulu-test.en.json');
        }
    }

    public function testXliffExport()
    {
        $this->export->setLocale('en');
        $this->export->setFilename('sulu-test');
        $this->export->setFormat(Export::XLIFF);
        $this->export->setPath(self::$fixturePath . '/');
        $this->export->execute();

        $expectedHash = file_get_contents(self::$fixturePath . '/shouldbes/sulu-test.en.xlf');
        $actualHash = file_get_contents(self::$fixturePath . '/sulu-test.en.xlf');

        $this->assertSame($expectedHash, $actualHash);
    }

    public function testJsonExport()
    {
        $this->export->setLocale('en');
        $this->export->setFilename('sulu-test');
        $this->export->setFormat(Export::JSON);
        $this->export->setPath(self::$fixturePath . '/');
        $this->export->execute();

        $expectedHash = file_get_contents(self::$fixturePath . '/shouldbes/sulu-test.en.json');
        $actualHash = file_get_contents(self::$fixturePath . '/sulu-test.en.json');

        $this->assertSame($expectedHash, $actualHash);
    }

    public function testExportWithFrontEnd()
    {
        $this->export->setLocale('en');
        $this->export->setFilename('sulu-test.frontend');
        $this->export->setFrontend(true);
        $this->export->setFormat(Export::XLIFF);
        $this->export->setPath(self::$fixturePath . '/');
        $this->export->execute();

        $expectedHash = file_get_contents(self::$fixturePath . '/shouldbes/sulu-test.frontend.en.xlf');
        $actualHash = file_get_contents(self::$fixturePath . '/sulu-test.frontend.en.xlf');

        $this->assertSame($expectedHash, $actualHash);
    }
}
