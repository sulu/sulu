<?php


namespace Sulu\Component\Webspace;


class DefaultTemplate
{
    private $type;
    private $template;
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
}
