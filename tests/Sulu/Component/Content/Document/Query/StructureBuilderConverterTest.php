<?php

namespace Sulu\Component\Content\Document\Query;

use PHPCR\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\Content\Document\Query\StructureBuilderConverter;
use PHPCR\Query\QueryManagerInterface;
use PHPCR\WorkspaceInterface;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Sulu\Component\Content\Document\Subscriber\StructureSubscriber;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;

class StructureBuilderConverterTest extends SuluTestCase
{
    public function setUp()
    {
        $this->initPhpcr();

        $this->session = $this->getContainer()->get('doctrine_phpcr.default_session');
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->localization1 = $this->prophesize(Localization::class);
        $this->localization2 = $this->prophesize(Localization::class);
        $this->localization1->getLocalization()->willReturn('de');
        $this->localization2->getLocalization()->willReturn('fr');
        $this->webspaceManager->getAllLocalizations()->willReturn(array(
            $this->localization1,
            $this->localization2
        ));
        $this->localizedDocument = $this->prophesize(LocalizedStructureBehavior::class);
        $this->structureFactory = $this->prophesize(StructureMetadataFactory::class);
        $this->structureMetadata = $this->prophesize(StructureMetadata::class);
        $this->propertyMetadata = $this->prophesize(PropertyMetadata::class);

        $this->converter = new StructureBuilderConverter(
            $this->session,
            $this->dispatcher->reveal(),
            $this->metadataFactory->reveal(),
            $this->encoder->reveal(),
            $this->webspaceManager->reveal(),
            $this->structureFactory->reveal()
        );
    }

    /**
     * It should parse a source document with a structure in all languages
     */
    public function testDocumentWithStructure()
    {
        $builder = $this->createBuilder(array(
            'document' => 'full',
            'structure' => 'overview',
            'structure_field' => null,
        ));

        $this->encoder->localizedSystemName(StructureSubscriber::STRUCTURE_TYPE_FIELD, 'de')->willReturn('lsys:de-structype');

        $query = $builder->getQuery();
        $sql2 = $query->getPhpcrQuery()->getStatement();
        $this->assertEquals('SELECT * FROM [nt:unstructured] AS f WHERE (f.[jcr:mixinTypes] = \'hello\' AND (f.[lsys:de-structype] = \'overview\' OR f.[lsys:fr-structype] = \'overview\'))', $sql2);
    }

    /**
     * It should parse a source document with a structure in a specified locale
     */
    public function testDocumentWithStructureSpecifiedLocale()
    {
        $builder = $this->createBuilder(array(
            'document' => 'full',
            'structure' => 'overview',
        ));
        $builder->setLocale('fr');

        $query = $builder->getQuery();
        $sql2 = $query->getPhpcrQuery()->getStatement();
        $this->assertEquals('SELECT * FROM [nt:unstructured] AS f WHERE (f.[jcr:mixinTypes] = \'hello\' AND f.[lsys:fr-structype] = \'overview\')', $sql2);
    }

    /**
     * It use a structure property in a criteria
     */
    public function testStructureProeprtyCriteria()
    {
        $builder = $this->createBuilder(array(
            'document' => 'full',
            'document_alias' => 'f',
            'structure' => 'overview',
            'structure_field' => 'title',
        ));
        $builder->setLocale('fr');

        $this->structureMetadata->getProperty('title')->willReturn($this->propertyMetadata->reveal());
        $this->encoder->fromProperty($this->propertyMetadata->reveal(), 'fr')->willReturn('i18n:fr-title');
        $this->propertyMetadata->isLocalized()->willReturn(true);

        $query = $builder->getQuery();
        $sql2 = $query->getPhpcrQuery()->getStatement();
        $this->assertEquals('SELECT * FROM [nt:unstructured] AS f WHERE ((f.[i18n:fr-title] = \'foobar\' AND f.[jcr:mixinTypes] = \'hello\') AND f.[lsys:fr-structype] = \'overview\')', $sql2);
    }

    /**
     * It should throw an exception when trying to select on a localized property when no locale has been set.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testStructurePropertyCriteriaNotLocalizedException()
    {
        $builder = $this->createBuilder(array(
            'document' => 'full',
            'structure' => 'overview',
            'structure_field' => 'title',
        ));
        $this->structureMetadata->getProperty('title')->willReturn($this->propertyMetadata->reveal());
        $this->propertyMetadata->isLocalized()->willReturn(true);

        $builder->getQuery();
    }

    private function createBuilder(array $options)
    {
        $builder =  new QueryBuilder();
        $builder->setConverter($this->converter);

        $source = $options['structure'] ? $options['document'] . '#' . $options['structure'] : $options['document'];
        $builder->from()->document($source, 'f');

        if (isset($options['structure_field'])) {
            $builder->where()->eq()->field('f.#' . $options['structure_field'])->literal('foobar');
        }

        $this->metadataFactory->hasAlias($options['document'])->willReturn(true);
        $this->metadataFactory->getMetadataForAlias($options['document'])->willReturn(
            $this->metadata->reveal()
        );
        $this->metadata->getPhpcrType()->willReturn('hello');
        $this->metadata->getAlias()->willReturn('full');
        $this->metadata->getReflection()->willReturn(new \ReflectionClass(get_class($this->localizedDocument->reveal())));
        $this->encoder->localizedSystemName(StructureSubscriber::STRUCTURE_TYPE_FIELD, 'fr')->willReturn('lsys:fr-structype');
        $this->structureFactory->getStructure('full', $options['structure'])->willReturn($this->structureMetadata->reveal());
        $this->structureMetadata->getName()->willReturn($options['structure']);

        return $builder;
    }
}
