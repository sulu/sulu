<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit;

use Sulu\Component\DocumentManager\DocumentRegistry;
use PHPCR\NodeInterface;

class DocumentRegistryTest extends \PHPUnit_Framework_TestCase
{
    private $registry;

    public function setUp()
    {
        $this->registry = new DocumentRegistry();
        $this->node = $this->prophesize(NodeInterface::class);
        $this->node->getIdentifier()->willReturn('1234');
        $this->document = new \stdClass;
    }

    /**
     * It should register a document and its associated PHPCR node
     */
    public function testRegisterDocument()
    {
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr');
        $this->assertTrue($this->registry->hasDocument($this->document));
        $this->assertTrue($this->registry->hasNode($this->node->reveal()));
    }

    /**
     * It should deregister a given document
     *
     * @depends testRegisterDocument
     */
    public function testDeregisterDocument()
    {
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr');
        $this->registry->deregisterDocument($this->document);
        $this->assertFalse($this->registry->hasDocument($this->document));
        $this->assertFalse($this->registry->hasNode($this->node->reveal()));
    }

    /**
     * It should throw an exception when an unregistered document is deregistered
     *
     * @expectedException \RuntimeException
     */
    public function testDeregisterDocumentUnknown()
    {
        $this->registry->deregisterDocument($this->document);
    }

    /**
     * It should return the PHPCR node for a registered document
     *
     * @depends testRegisterDocument
     */
    public function testGetNodeForDocument()
    {
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr');
        $this->assertSame(
            $this->node->reveal(),
            $this->registry->getNodeForDocument($this->document)
        );
    }

    /**
     * It should throw an exception if an unregistered document is passed to get node for document
     *
     * @expectedException \RuntimeException
     */
    public function testGetNodeForDocumentUnknown()
    {
        $this->registry->getNodeForDocument($this->document);
    }

    /**
     * It should return a document for a mangaed node
     *
     * @depends testRegisterDocument
     */
    public function testGetDocumentForNode()
    {
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr');
        $document = $this->registry->getDocumentForNode($this->node->reveal());
        $this->assertSame($this->document, $document);
    }

    /**
     * It should provide a method to clear the registry
     */
    public function testClear()
    {
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr');
        $this->assertTrue($this->registry->hasDocument($this->document));
        $this->registry->clear();
        $this->assertFalse($this->registry->hasDocument($this->document));
    }

    /**
     * It should be able to determine the locale of a document
     */
    public function testGetLocaleForDocument()
    {
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr');
        $this->assertEquals('fr', $this->registry->getLocaleForDocument($this->document));
    }
}
