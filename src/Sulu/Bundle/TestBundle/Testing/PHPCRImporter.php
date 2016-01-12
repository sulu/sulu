<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use PHPCR\ImportUUIDBehaviorInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Util\XmlUtil;
use Symfony\Component\Config\Util\XmlUtils;

class PHPCRImporter
{
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function import($fileName)
    {
        if ($this->session->getRootNode()->hasNode('cmf')) {
            $this->session->getNode('/cmf')->remove();
            $this->session->save();
        }

        $this->session->importXML(
            '/',
            $fileName,
            ImportUUIDBehaviorInterface::IMPORT_UUID_COLLISION_THROW
        );
        $this->session->save();

        $doc = XmlUtils::loadFile($fileName);
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('sv', 'http://www.jcp.org/jcr/sv/1.0');

        $data = [];
        /** @var \DOMNode $node */
        foreach ($xpath->query('//sv:value[text()="sulu:page"]/../..') as $node) {
            $parent = $node;
            $path = '';
            do {
                $path = '/' . XmlUtil::getValueFromXPath('@sv:name', $xpath, $parent) . $path;
                $parent = $parent->parentNode;
            } while (XmlUtil::getValueFromXPath('@sv:name', $xpath, $parent) !== 'contents');

            $data[] = [
                'id' => XmlUtil::getValueFromXPath('sv:property[@sv:name="jcr:uuid"]/sv:value', $xpath, $node),
                'path' => $path,
                'title' => XmlUtil::getValueFromXPath('sv:property[@sv:name="i18n:en-title"]/sv:value', $xpath, $node),
                'template' => XmlUtil::getValueFromXPath(
                    'sv:property[@sv:name="i18n:en-template"]/sv:value',
                    $xpath,
                    $node
                ),
                'url' => XmlUtil::getValueFromXPath('sv:property[@sv:name="i18n:en-url"]/sv:value', $xpath, $node),
                'article' => XmlUtil::getValueFromXPath(
                    'sv:property[@sv:name="i18n:en-article"]/sv:value',
                    $xpath,
                    $node
                ),
            ];
        }

        return $data;
    }
}
