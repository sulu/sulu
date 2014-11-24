<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\StructureSerializer;

use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;

/**
 * Serializer for structures
 */
class StructureSerializer
{
    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * Constructor
     */
    function __construct($structureManager)
    {
        $this->structureManager = $structureManager;
    }

    /**
     * Serializes given structure to array
     * @param StructureInterface $structure
     * @return array
     */
    public function serialize(StructureInterface $structure)
    {
        return $structure->toArray(true);
    }

    /**
     * Deserializes data to structure
     * @param array $data
     * @param string $type
     * @return StructureInterface
     */
    public function deserialize(array $data, $type = Structure::TYPE_PAGE)
    {
        if ($type !== Structure::TYPE_PAGE) {
            throw new \InvalidArgumentException(
                sprintf('Only type page implemented')
            );
        }

        if (!array_key_exists('template', $data)) {
            throw new \InvalidArgumentException(
                sprintf('Data doesn´t seems to be a serialized structure. No template given.')
            );
        }

        $result = $this->structureManager->getStructure($data['template'], $type);

        $result->setUuid($data['id']);
        $result->setHasChildren($data['hasSub']);
        $result->setPublished($data['published']);

        $result->setCreator($data['creator']);
        $result->setChanger($data['changer']);
        $result->setCreated($data['created']);
        $result->setChanged($data['changed']);

        if ($result instanceof Structure\Page) {
            $result->setPath($data['path']);
            $result->setEnabledShadowLanguages($data['enabledShadowLanguages']);
            $result->setConcreteLanguages($data['concreteLanguages']);
            $result->setIsShadow($data['shadowOn']);
            $result->setShadowBaseLanguage($data['shadowBaseLanguage']);
            $result->setNodeState($data['nodeState']);
            $result->setNavContexts($data['navContexts']);
            $result->setOriginTemplate($data['originTemplate']);
            $result->setExt($data['ext']);
        }

        foreach ($result->getProperties(true) as $property) {
            if (array_key_exists($property->getName(), $data)) {
                $property->setValue($data[$property->getName()]);
            }
        }

        return $result;
    }
}
