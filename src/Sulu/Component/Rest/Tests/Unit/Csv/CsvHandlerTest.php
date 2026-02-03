<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\Csv;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Rest\Csv\CsvHandler;
use Sulu\Component\Rest\Csv\ObjectNotSupportedException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvHandlerTest extends TestCase
{
    use ProphecyTrait;

    private CsvHandler $handler;

    /** @var ObjectProphecy<ViewHandlerInterface> */
    private ObjectProphecy $viewHandler;

    public function setUp(): void
    {
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $this->handler = new CsvHandler($serializer->reveal());
        $this->viewHandler = $this->prophesize(ViewHandlerInterface::class);
    }

    public function testNonListResponse(): void
    {
        $this->expectException(ObjectNotSupportedException::class);
        $object = new \stdClass();

        $view = new View($object);
        $format = 'csv';
        $request = self::createRequest();

        $this->handler->createResponse($this->viewHandler->reveal(), $view, $request, $format);
    }

    #[DataProvider('dataListRepresentation')]
    public function testListRepresentation(Request $request): void
    {
        $listRepresentation = $this->prophesize(ListRepresentation::class);
        $listRepresentation->getRel()->willReturn('contacts');
        $listRepresentation->getData()->willReturn(
            [
                ['id' => 1, 'fullName' => 'Max Mustermann', 'birthday' => new \DateTime('1976-02-01T00:00:00+01:00'), 'enabled' => true],
                ['id' => 2, 'fullName' => 'Erika Mustermann', 'birthday' => new \DateTime('1964-08-12T00:00:00+01:00'), 'enabled' => false],
            ]
        );

        $view = new View($listRepresentation->reveal());
        $format = 'csv';

        \ob_start();
        $response = $this->handler->createResponse($this->viewHandler->reveal(), $view, $request, $format);
        $response->send();
        $content = \ob_get_contents();
        \ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertHeaders($response);
        $this->assertEquals(
            "id;fullName;birthday;enabled\n1;\"Max Mustermann\";1976-02-01T00:00:00+01:00;1\n2;\"Erika Mustermann\";1964-08-12T00:00:00+01:00;0\n",
            $content
        );
    }

    /**
     * @return \Generator<array{Request}>
     */
    public static function dataListRepresentation(): \Generator
    {
        yield 'empty query parameters should not crash' => [new Request()];

        yield 'request with query parameters' => [self::createRequest()];
    }

    public function testCollectionRepresentation(): void
    {
        $collectionRepresentation = $this->prophesize(CollectionRepresentation::class);
        $collectionRepresentation->getRel()->willReturn('contacts');
        $collectionRepresentation->getData()->willReturn(
            [
                ['id' => 1, 'fullName' => 'Max Mustermann', 'birthday' => new \DateTime('1976-02-01T00:00:00+01:00'), 'enabled' => true],
                ['id' => 2, 'fullName' => 'Erika Mustermann', 'birthday' => new \DateTime('1964-08-12T00:00:00+01:00'), 'enabled' => false],
            ]
        );

        $view = new View($collectionRepresentation->reveal());
        $format = 'csv';

        $request = self::createRequest();

        \ob_start();
        $response = $this->handler->createResponse($this->viewHandler->reveal(), $view, $request, $format);
        $response->send();
        $content = \ob_get_contents();
        \ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertHeaders($response);
        $this->assertEquals(
            "id;fullName;birthday;enabled\n1;\"Max Mustermann\";1976-02-01T00:00:00+01:00;1\n2;\"Erika Mustermann\";1964-08-12T00:00:00+01:00;0\n",
            $content
        );
    }

    public function testListRepresentationDifferentConfig(): void
    {
        $listRepresentation = $this->prophesize(ListRepresentation::class);
        $listRepresentation->getRel()->willReturn('contacts');
        $listRepresentation->getData()->willReturn(
            [
                ['id' => 1, 'fullName' => 'Max Mustermann', 'birthday' => new \DateTime('1976-02-01T00:00:00+01:00')],
                ['id' => 2, 'fullName' => 'Erika Mustermann', 'birthday' => new \DateTime('1964-08-12T00:00:00+01:00')],
            ]
        );

        $view = new View($listRepresentation->reveal());
        $format = 'csv';

        $request = self::createRequest(['delimiter' => ',', 'enclosure' => '\'', 'escape' => '!', 'newLine' => '\\r\\n']);

        \ob_start();
        $response = $this->handler->createResponse($this->viewHandler->reveal(), $view, $request, $format);
        $response->send();
        $content = \ob_get_contents();
        \ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertHeaders($response);
        $this->assertEquals(
            "id,fullName,birthday\r\n1,'Max Mustermann',1976-02-01T00:00:00+01:00\r\n2,'Erika Mustermann',1964-08-12T00:00:00+01:00\r\n",
            $content
        );
    }

    public function testListRepresentationWithArray(): void
    {
        $listRepresentation = $this->prophesize(ListRepresentation::class);
        $listRepresentation->getRel()->willReturn('contacts');
        $listRepresentation->getData()->willReturn(
            [
                ['id' => 1, 'fullName' => 'Max Mustermann', 'properties' => ['test' => 1]],
                ['id' => 2, 'fullName' => 'Erika Mustermann', 'properties' => ['test' => 2]],
            ]
        );

        $view = new View($listRepresentation->reveal());
        $format = 'csv';
        $request = self::createRequest();

        \ob_start();
        $response = $this->handler->createResponse($this->viewHandler->reveal(), $view, $request, $format);
        $response->send();
        $content = \ob_get_contents();
        \ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertHeaders($response);
        $this->assertEquals(
            "id;fullName;properties\n1;\"Max Mustermann\";\"{\"\"test\"\":1}\"\n2;\"Erika Mustermann\";\"{\"\"test\"\":2}\"\n",
            $content
        );
    }

    public function testListRepresentationEmpty(): void
    {
        $listRepresentation = $this->prophesize(ListRepresentation::class);
        $listRepresentation->getRel()->willReturn('contacts');
        $listRepresentation->getData()->willReturn([]);

        $view = new View($listRepresentation->reveal());
        $format = 'csv';

        $request = self::createRequest();

        \ob_start();
        $response = $this->handler->createResponse($this->viewHandler->reveal(), $view, $request, $format);
        $response->send();
        $content = \ob_get_contents();
        \ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertHeaders($response);
        $this->assertEquals('', $content);
    }

    /**
     * @param array<string, string> $config
     */
    private static function createRequest(array $config = []): Request
    {
        $request = new Request();
        $request->query->add([
            'delimiter' => ';',
            'enclosure' => '"',
            'escape' => '\\',
            'newLine' => '\\n',
            ...$config,
        ]);

        return $request;
    }

    private function assertHeaders(Response $response): void
    {
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
        $this->assertEquals(
            'attachment; filename=contacts.csv',
            \str_replace('"', '', $response->headers->get('Content-Disposition'))
        );
    }
}
