<?php

namespace Sulu\Component\Content\Document\Query;

use Sulu\Component\DocumentManager\Query\BuilderConverterSulu;
use Doctrine\ODM\PHPCR\Query\Builder\SourceDocument;
use Doctrine\ODM\PHPCR\Query\Builder\OperandDynamicField;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use PHPCR\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Content\Document\Subscriber\StructureSubscriber;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;

class StructureBuilderConverter extends BuilderConverterSulu
{
    private $structureMetadata = array();
    private $encoder;
    private $webspaceManager;
    private $structureFactory;

    public function __construct(
        SessionInterface $session,
        EventDispatcherInterface $dispatcher,
        MetadataFactoryInterface $factory,
        PropertyEncoder $encoder,
        WebspaceManagerInterface $webspaceManager,
        StructureMetadataFactory $structureFactory
    ) {
        parent::__construct($session, $dispatcher, $factory);
        $this->encoder = $encoder;
        $this->webspaceManager = $webspaceManager;
        $this->structureFactory = $structureFactory;
    }

    public function getQuery(QueryBuilder $builder)
    {
        $this->structureMetadata = array();
        return parent::getQuery($builder);
    }

    public function walkSourceDocument(SourceDocument $node)
    {
        $documentClass = $node->getDocumentFqn();

        $structure = strstr($documentClass, '#');


        if (false !== $structure) {
            $documentClass = substr($documentClass, 0, -strlen($structure));
            $structure = substr($structure, 1);

            // replace the source document without the structure suffix
            $node = new SourceDocument($node->getParent(), $documentClass, $node->getAlias());
            $alias = parent::walkSourceDocument($node);

            $documentAlias = $this->documentMetadata[$node->getAlias()]->getAlias();
            $this->structureMetadata[$node->getAlias()] = $this->structureFactory->getStructure($documentAlias, $structure);

            return $alias;
        }

        return parent::walkSourceDocument($node);
    }

    protected function applySourceConstraints(QueryBuilder $builder)
    {
        parent::applySourceConstraints($builder);

        $locales = $builder->getLocale() ? array($builder->getLocale()) : $this->getAllLocales();

        foreach ($this->structureMetadata as $alias => $structureMetadata) {
            $compositeConstraint = null;
            $reflection = $this->documentMetadata[$alias]->getReflection();
            foreach ($locales as $locale) {

                if ($reflection->isSubclassOf(LocalizedStructureBehavior::class)) {
                    $structureTypeProp = $this->encoder->localizedSystemName(StructureSubscriber::STRUCTURE_TYPE_FIELD, $locale);
                } else {
                    $structureTypeProp = $this->encoder->systemName(StructureSubscriber::STRUCTURE_TYPE_FIELD);
                }

                $structureConstraint = $this->qomf->comparison(
                    $this->qomf->propertyValue(
                        $alias,
                        $structureTypeProp
                    ),
                    QOMConstants::JCR_OPERATOR_EQUAL_TO,
                    $this->qomf->literal($structureMetadata->getName())
                );

                if (null === $compositeConstraint) {
                    $compositeConstraint = $structureConstraint;
                } else {
                    $compositeConstraint = $this->qomf->orConstraint(
                        $compositeConstraint,
                        $structureConstraint
                    );
                }
            }

            $this->constraint = $this->qomf->andConstraint(
                $this->constraint,
                $compositeConstraint
            );
        }
    }

    /**
     * TODO: There should be a better way to get the list of locales
     *       https://github.com/sulu-io/sulu/issues/1179
     */
    private function getAllLocales()
    {
        $locales = array();
        foreach ($this->webspaceManager->getAllLocalizations() as $localization) {
            $locales[] = $localization->getLocalization();
        }

        return $locales;
    }

    /**
     * {@inheritDoc}
     */
    protected function walkOperandDynamicField(OperandDynamicField $node)
    {
        $alias = $node->getAlias();
        $field = $node->getField();

        list($alias, $phpcrName) = $this->getPhpcrProperty(
            $alias,
            $field
        );

        $operand = $this->qomf->propertyValue(
            $alias,
            $phpcrName
        );

        return $operand;
    }

    protected function getPhpcrProperty($alias, $field)
    {
        if (0 === strpos($field, '#')) {
            return $this->getStructurePhpcrProperty($alias, substr($field, 1));
        }

        return parent::getPhpcrProperty($alias, $field);
    }

    /**
     * Return the PHPCR property name for the 
     *
     * @param mixed $alias
     * @param mixed $field
     */
    protected function getStructurePhpcrProperty($alias, $propertyName)
    {
        if (!isset($this->structureMetadata[$alias])) {
            throw new \InvalidArgumentException(sprintf(
                'No structure has been registered in the query against alias "%s"',
                $alias
            ));
        }

        $propertyMetadata = $this->structureMetadata[$alias]->getProperty($propertyName);

        if ($propertyMetadata->isLocalized() && null === $this->locale) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot use use localized property "%s" with no localization. Use $builder->setLocale($locale)',
                $propertyName
            ));
        }

        return array($alias, $this->encoder->fromProperty($propertyMetadata, $this->locale));
    }
}
