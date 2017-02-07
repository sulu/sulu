<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Csv;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Goodby\CSV\Export\Standard\Collection\CallbackCollection;
use Goodby\CSV\Export\Standard\Exporter;
use Goodby\CSV\Export\Standard\ExporterConfig;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Creates responses for csv-requests.
 */
class CsvHandler
{
    /**
     * Translates request value to real new-lines.
     *
     * @var array
     */
    public static $newLineMap = [
        '\\n' => "\n",
        '\\r\\n' => "\r\n",
        '\\r' => "\r",
    ];

    /**
     * Translates request value to real delimiter.
     *
     * @var array
     */
    public static $delimiterMap = [
        '\\t' => "\t",
    ];

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Handles response for csv-request.
     *
     * @param ViewHandler $handler
     * @param View $view
     * @param Request $request
     * @param string $format
     *
     * @return Response
     *
     * @throws ObjectNotSupportedException
     */
    public function createResponse(ViewHandler $handler, View $view, Request $request, $format)
    {
        if (!$view->getData() instanceof ListRepresentation) {
            throw new ObjectNotSupportedException($view);
        }

        $viewData = $view->getData();
        $data = new CallbackCollection($viewData->getData(), [$this, 'prepareData']);
        $fileName = sprintf('%s.csv', $viewData->getRel());

        $config = new ExporterConfig();
        $exporter = new Exporter($config);

        $data->rewind();
        if ($row = $data->current()) {
            $config->setColumnHeaders(array_keys($row));
        }

        $config->setDelimiter($this->convertValue($request->get('delimiter', ';'), self::$delimiterMap));
        $config->setNewline($this->convertValue($request->get('newLine', '\\n'), self::$newLineMap));
        $config->setEnclosure($request->get('enclosure', '"'));
        $config->setEscape($request->get('escape', '\\'));

        $response = new StreamedResponse();
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName,
            $fileName
        );
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', $disposition);
        $response->setCallback(
            function () use ($data, $exporter) {
                $exporter->export('php://output', $data);
            }
        );
        $response->send();

        return $response;
    }

    /**
     * The exporter is not able to write DateTime objects into csv. This method converts them to string.
     *
     * @param mixed $row
     *
     * @return array
     */
    public function prepareData($row)
    {
        if (!$row) {
            return $row;
        }

        if (!is_array($row)) {
            $row = $this->serializer->serialize($row, 'array', SerializationContext::create()->setSerializeNull(true));
        }

        foreach ($row as $key => $value) {
            if ($value instanceof \DateTime) {
                $row[$key] = $value->format(\DateTime::RFC3339);
            } elseif (is_array($value) || is_object($value)) {
                $row[$key] = json_encode($value);
            }
        }

        return $row;
    }

    /**
     * Return mapped value or value itself.
     *
     * @param string $value
     * @param array $map
     *
     * @return string
     */
    private function convertValue($value, array $map)
    {
        if (array_key_exists($value, $map)) {
            return $map[$value];
        }

        return $value;
    }
}
