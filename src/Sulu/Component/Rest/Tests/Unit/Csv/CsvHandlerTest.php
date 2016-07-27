<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\Csv;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use JMS\Serializer\SerializerInterface;
use Sulu\Component\Rest\Csv\CsvHandler;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Sulu\Component\Rest\Csv\ObjectNotSupportedException
     */
    public function testNonListResponse()
    {
        $object = new \stdClass();

        $viewHandler = $this->prophesize(ViewHandler::class);
        $view = $this->prophesize(View::class);
        $request = $this->prophesize(Request::class);
        $serializer = $this->prophesize(SerializerInterface::class);
        $format = 'csv';

        $view->getData()->willReturn($object);

        $handler = new CsvHandler($serializer->reveal());
        $handler->createResponse($viewHandler->reveal(), $view->reveal(), $request->reveal(), $format);
    }

    public function testListRepresentation()
    {
        $listRepresentation = $this->prophesize(ListRepresentation::class);
        $listRepresentation->getRel()->willReturn('contacts');
        $listRepresentation->getData()->willReturn(
            [
                ['id' => 1, 'fullName' => 'Max Mustermann', 'birthday' => new \DateTime('1976-02-01T00:00:00+01:00')],
                ['id' => 2, 'fullName' => 'Erika Mustermann', 'birthday' => new \DateTime('1964-08-12T00:00:00+01:00')],
            ]
        );

        $viewHandler = $this->prophesize(ViewHandler::class);
        $view = $this->prophesize(View::class);
        $request = $this->prophesize(Request::class);
        $serializer = $this->prophesize(SerializerInterface::class);
        $format = 'csv';

        $request->get('delimiter', ';')->willReturn(';');
        $request->get('enclosure', '"')->willReturn('"');
        $request->get('escape', '\\')->willReturn('\\');
        $request->get('newLine', '\\n')->willReturn('\\n');

        $view->getData()->willReturn($listRepresentation->reveal());

        $handler = new CsvHandler($serializer->reveal());

        ob_start();
        $response = $handler->createResponse($viewHandler->reveal(), $view->reveal(), $request->reveal(), $format);
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals($response->headers->get('Content-Type'), 'text/csv');
        $this->assertEquals($response->headers->get('Content-Disposition'), 'attachment; filename="contacts.csv"');

        $this->assertEquals(
            "id;fullName;birthday\n1;\"Max Mustermann\";1976-02-01T00:00:00+01:00\n2;\"Erika Mustermann\";1964-08-12T00:00:00+01:00\n",
            $content
        );
    }

    public function testListRepresentationDifferentConfig()
    {
        $listRepresentation = $this->prophesize(ListRepresentation::class);
        $listRepresentation->getRel()->willReturn('contacts');
        $listRepresentation->getData()->willReturn(
            [
                ['id' => 1, 'fullName' => 'Max Mustermann', 'birthday' => new \DateTime('1976-02-01T00:00:00+01:00')],
                ['id' => 2, 'fullName' => 'Erika Mustermann', 'birthday' => new \DateTime('1964-08-12T00:00:00+01:00')],
            ]
        );

        $viewHandler = $this->prophesize(ViewHandler::class);
        $view = $this->prophesize(View::class);
        $request = $this->prophesize(Request::class);
        $serializer = $this->prophesize(SerializerInterface::class);
        $format = 'csv';

        $request->get('delimiter', ';')->willReturn(',');
        $request->get('enclosure', '"')->willReturn('\'');
        $request->get('escape', '\\')->willReturn('"');
        $request->get('newLine', '\\n')->willReturn('\\r\\n');

        $view->getData()->willReturn($listRepresentation->reveal());

        $handler = new CsvHandler($serializer->reveal());

        ob_start();
        $response = $handler->createResponse($viewHandler->reveal(), $view->reveal(), $request->reveal(), $format);
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals($response->headers->get('Content-Type'), 'text/csv');
        $this->assertEquals($response->headers->get('Content-Disposition'), 'attachment; filename="contacts.csv"');

        $this->assertEquals(
            "id,fullName,birthday\r\n1,'Max Mustermann',1976-02-01T00:00:00+01:00\r\n2,'Erika Mustermann',1964-08-12T00:00:00+01:00\r\n",
            $content
        );
    }

    public function testListRepresentationWithArray()
    {
        $listRepresentation = $this->prophesize(ListRepresentation::class);
        $listRepresentation->getRel()->willReturn('contacts');
        $listRepresentation->getData()->willReturn(
            [
                ['id' => 1, 'fullName' => 'Max Mustermann', 'properties' => ['test' => 1]],
                ['id' => 2, 'fullName' => 'Erika Mustermann', 'properties' => ['test' => 2]],
            ]
        );

        $viewHandler = $this->prophesize(ViewHandler::class);
        $view = $this->prophesize(View::class);
        $request = $this->prophesize(Request::class);
        $serializer = $this->prophesize(SerializerInterface::class);
        $format = 'csv';

        $request->get('delimiter', ';')->willReturn(';');
        $request->get('enclosure', '"')->willReturn('"');
        $request->get('escape', '\\')->willReturn('\\');
        $request->get('newLine', '\\n')->willReturn('\\n');

        $view->getData()->willReturn($listRepresentation->reveal());

        $handler = new CsvHandler($serializer->reveal());

        ob_start();
        $response = $handler->createResponse($viewHandler->reveal(), $view->reveal(), $request->reveal(), $format);
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals($response->headers->get('Content-Type'), 'text/csv');
        $this->assertEquals($response->headers->get('Content-Disposition'), 'attachment; filename="contacts.csv"');

        $this->assertEquals(
            "id;fullName;properties\n1;\"Max Mustermann\";\"{\"\"test\"\":1}\"\n2;\"Erika Mustermann\";\"{\"\"test\"\":2}\"\n",
            $content
        );
    }

    public function testListRepresentationEmpty()
    {
        $listRepresentation = $this->prophesize(ListRepresentation::class);
        $listRepresentation->getRel()->willReturn('contacts');
        $listRepresentation->getData()->willReturn([]);

        $viewHandler = $this->prophesize(ViewHandler::class);
        $view = $this->prophesize(View::class);
        $request = $this->prophesize(Request::class);
        $serializer = $this->prophesize(SerializerInterface::class);
        $format = 'csv';

        $request->get('delimiter', ';')->willReturn(';');
        $request->get('enclosure', '"')->willReturn('"');
        $request->get('escape', '\\')->willReturn('\\');
        $request->get('newLine', '\\n')->willReturn('\\n');

        $view->getData()->willReturn($listRepresentation->reveal());

        $handler = new CsvHandler($serializer->reveal());

        ob_start();
        $response = $handler->createResponse($viewHandler->reveal(), $view->reveal(), $request->reveal(), $format);
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals($response->headers->get('Content-Type'), 'text/csv');
        $this->assertEquals($response->headers->get('Content-Disposition'), 'attachment; filename="contacts.csv"');

        $this->assertEquals('', $content);
    }
}
