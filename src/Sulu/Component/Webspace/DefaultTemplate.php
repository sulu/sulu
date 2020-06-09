<?php


namespace Sulu\Component\Webspace;


use JMS\Serializer\Annotation as Serializer;
use Sulu\Component\Util\ArrayableInterface;

class DefaultTemplate implements \JsonSerializable, ArrayableInterface
{
    /**
     * @var string
     * @Serializer\Groups({"frontend", "Default"})
     */
    private $type;

    /**
     * @var string
     * @Serializer\Groups({"frontend", "Default"})
     */
    private $template;

    /**
     * @var string|null
     * @Serializer\Groups({"frontend", "Default"})
     */
    private $parentTemplate;

    public function __construct(string $type, string $template, ?string $parentTemplate = null)
    {
        $this->type = $type;
        $this->template = $template;
        $this->parentTemplate = $parentTemplate;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getParentTemplate(): ?string
    {
        return $this->parentTemplate;
    }

    public function toArray($depth = null)
    {
        return [
            'template' => $this->template,
            'type' => $this->type,
            'parentTemplate' => $this->parentTemplate
        ];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
