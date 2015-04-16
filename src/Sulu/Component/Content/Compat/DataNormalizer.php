<?php

namespace Sulu\Component\Content\Compat;

use Sulu\Component\Content\Structure as LegacyStructure;
use PHPCR\Util\PathHelper;
use Sulu\Component\Content\Document\WorkflowStage;

/**
 * Normalizes the legacy Sulu request data
 */
class DataNormalizer
{
    /**
     * Normalize incoming data from the legacy node controller
     *
     * @param mixed $data
     * @param mixed $state Translates to the workflow state
     */
    public function normalize($data, $state, $parentUuid)
    {
        unset(
            $data['type'],
            $data['creator'],
            $data['linked'],
            $data['changer'],
            $data['breadcrumb'],
            $data['originTemplate'],
            $data['changed'],
            $data['changer'],
            $data['path'],
            $data['nodeState'],
            $data['internal'],
            $data['concreteLanguages'],
            $data['hasSub'],
            $data['published'],
            $data['enabledShadowLanguages'],
            $data['shadowEnabled'],
            $data['publishedState'],
            $data['created'],
            $data['_embedded'],
            $data['_links'],
            $data['navigation'],
            $data['id']
        );

        $normalized = array(
            'title' => $this->getAndUnsetValue($data, 'title'),
            'resourceSegment' => isset($data['url']) ? $data['url'] : null,
            'redirectType' => $this->getAndUnsetRedirectType($data),
            'extensions' => $this->getAndUnsetValue($data, 'ext'),
            'redirectTarget' => $this->getAndUnsetValue($data, 'internal_link'),
            'redirectExternal' => $this->getAndUnsetValue($data, 'external_link'),
            'navigationContexts' => $this->getAndUnsetValue($data, 'navContexts'),
            'workflowStage' => $this->getWorkflowStage($state),
            'shadowLocaleEnabled' => $this->getAndUnsetValue($data, 'shadowOn'),
            'shadowLocale' => $this->getAndUnsetValue($data, 'shadowBaseLanguage'),
            'structureType' => $this->getAndUnsetValue($data, 'template'),
            'content' => $data,
        );

        foreach ($normalized as $key => $value) {
            if (null === $value) {
                unset($normalized[$key]);
            }
        }

        if ($parentUuid) {
            $normalized['parent'] = $parentUuid;
        }

        return $normalized;
    }

    private function getAndUnsetValue(&$data, $key)
    {
        $value = null;

        if (isset($data[$key])) {
            $value = $data[$key];
            unset($data[$key]);
        }

        return $value;
    }

    private function getWorkflowStage($state)
    {
        if ($state === WorkflowStage::PUBLISHED) {
            return WorkflowStage::PUBLISHED;
        }

        return WorkflowStage::TEST;
    }

    private function getAndUnsetRedirectType(&$data)
    {
        if (!isset($data['nodeType'])) {
            return null;
        }

        $nodeType = $data['nodeType'];
        unset($data['nodeType']);

        return $nodeType;
    }
}
