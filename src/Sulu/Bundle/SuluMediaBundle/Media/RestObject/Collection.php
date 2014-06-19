<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\RestObject;

use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Bundle\MediaBundle\Entity\Collection as Entity;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\TestBundle\Entity\TestUser;
use DateTime;

class Collection extends ApiEntity implements RestObjectInterface
{

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var array
     */
    protected $style;

    /**
     * @var int
     */
    protected $type;

    /**
     * @var array
     */
    protected $children = array();

    /**
     * @var array
     */
    protected $formats = array();

    /**
     * @var int
     */
    protected $mediaNumber;

    /**
     * @var int
     */
    protected $parent;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $changer;

    /**
     * @var string
     */
    protected $creator;

    /**
     * @var string
     */
    protected $changed;

    /**
     * @var string
     */
    protected $created;

    /**
     * @var array
     */
    protected $properties = array();

    /**
     * {@inheritdoc}
     */
    public function setDataByEntityArray($data, $locale)
    {
        $this->locale = $locale;
        $mediaCount = 0;

        foreach ($data as $key => $value) {
            switch ($key) {
                // set id
                case 'id':
                    $this->id = $value;
                    break;
                // set style
                case 'style':
                    if ($value) {
                        $this->style = json_decode($value, true);
                    }
                    break;
                // set title and description
                case 'meta':
                    $counter = 0;
                    foreach ($value as $meta) {
                        $counter++;
                        if ($counter == 1 || $meta['locale'] == $locale) {
                            $this->title = $meta['title'];
                            $this->description = $meta['description'];
                        }
                    }
                    break;
                // set children
                case 'children':
                    $childrenIds = array();
                    if ($value) {
                        foreach ($value as $child) {
                            array_push($childrenIds, $child['id']);
                            if ($child['media']) {
                                // increase media count
                                $mediaCount += count($child['media']);
                            }
                        }
                    }
                    $this->children = $childrenIds;
                    break;
                // set parent and type
                case 'parent':
                case 'type':
                    if ($value) {
                        $this->$key = $value['id'];
                    }
                    break;
                // set changer and creator
                case 'changer':
                case 'creator':
                    if (isset($value['contact']['firstName']) && isset($value['contact']['lastName'])) {
                        $this->$key = $value['contact']['firstName'] . ' ' . $value['contact']['lastName'];
                    }
                    break;
                // set changed and create time
                case 'changed':
                case 'created':
                    if ($value instanceof DateTime) {
                        $this->$key = $value;
                    }
                    break;
                // increase media count
                case 'media':
                    if ($value) {
                        $mediaCount += count($value);
                    }
                    break;
                case 'lft':
                case 'rgt':
                case 'depth':
                    // ignored fields
                    break;
                default:
                    // set custom set strings and integers as properties
                    if (is_string($value) || is_int($value)) {
                        $this->properties[$key] = $value;
                    }
                    break;
            }
        }
        $this->mediaNumber = $mediaCount;

        return $this;
    }

    /**
     * @var Entity $object
     * {@inheritdoc}
     */
    public function setDataByEntity($object, $locale, $properties = array())
    {
        $mediaCount = 0;

        // set id
        $this->id = $object->getId();

        // set locale
        $this->locale = $locale;

        // set type
        $type = $object->getType();
        if ($type) {
            $this->type = $type->getId();
        }

        // set style
        $this->style = json_decode($object->getStyle(), true);

        // set children and get media counts
        $childIds = array();
        /**
         * @var Entity $child
         */
        foreach ($object->getChildren() as $child) {
            array_push($childIds, $child->getId());
            $mediaCount += count($child->getMedia());
        }

        // set media count
        $mediaCount += count($object->getMedia());
        $this->mediaNumber = $mediaCount;

        // set changed time
        if ($object->getChanged() instanceof DateTime) {
            $this->changed = $object->getChanged();
        }

        // set created time
        if ($object->getCreated() instanceof DateTime) {
            $this->created = $object->getCreated();
        }

        // set changer
        if ($object->getChanger()) {
            $this->changer = ''; // TODO
        }

        // set creator
        if ($object->getCreator()) {
            $this->creator = ''; // TODO
        }

        // set title and description
        $title = null;
        $description = null;
        /**
         * @var CollectionMeta $meta
         */
        $counter = 0;
        foreach ($object->getMeta() as $meta) {
            $counter++;
            // get locale meta or first meta
            if ($meta->getLocale() == $locale || $counter == 1) {
                $title = $meta->getTitle();
                $description = $meta->getDescription();
            }
        }
        $this->title = $title;
        $this->description = $description;

        // set properties
        $this->properties = $properties;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($fields = array())
    {
        if (empty($fields)) {
            // get all fields
            $data = array(
                'id' => $this->id,
                'locale' => $this->locale,
                'style' => $this->style,
                'type' => $this->type,
                'children' => $this->children,
                'mediaNumber' => $this->mediaNumber,
                'parent' => $this->parent,
                'title' => $this->title,
                'description' => $this->description,
                'formats' => $this->formats,
                'properties' => $this->properties,
                'changer' => $this->changer,
                'creator' => $this->creator,
                'changed' => $this->changed,
                'created' => $this->created,
            );
        } else {
            // only get specific fields
            $data = array();
            foreach ($fields as $field) {
                if (isset($this->$field)) {
                    $data[$field] = $this->$field;
                }
            }
        }

        return $data;
    }

    /**
     * @param string $changed
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    }

    /**
     * @return string
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @param string $changer
     * @return $this
     */
    public function setChanger($changer)
    {
        $this->changer = $changer;
        return $this;
    }

    /**
     * @return string
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * @param array $children
     * @return $this
     */
    public function setChildren($children)
    {
        $this->children = $children;
        return $this;
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param string $created
     * @return $this
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param string $creator
     * @return $this
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param int $mediaNumber
     * @return $this
     */
    public function setMediaNumber($mediaNumber)
    {
        $this->mediaNumber = $mediaNumber;
        return $this;
    }

    /**
     * @return int
     */
    public function getMediaNumber()
    {
        return $this->mediaNumber;
    }

    /**
     * @param int $parent
     * @return $this
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return int
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param array $style
     * @return $this
     */
    public function setStyle($style)
    {
        $this->style = $style;
        return $this;
    }

    /**
     * @return array
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param int $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param array $properties
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * @param array $formats
     */
    public function setFormats($formats)
    {
        $this->formats = $formats;
    }


    /**
     * @param $thumbnail
     */
    public function addThumbnail($thumbnail)
    {
        $this->formats[] = $thumbnail;
    }

} 
