<?php

namespace Sulu\Bundle\TestBundle\Behat;

use Behat\MinkExtension\Context\MinkContext as BaseMinkContext;

class MinkContext extends BaseMinkContext
{
    public function visit($page)
    {
        $currentUrl = $this->getCurrentUrl();
        if ($currentUrl == $page) {
            $this->getSession()->reload();
            return;
        }

        parent::visit($page);
    }


    protected function getCurrentUrl()
    {
        $parts = parse_url($this->getSession()->getCurrentUrl());
        $currentUrl = $parts['path'];

        if (isset($parts['query'])) {
            $currentUrl .= '?' . $parts['query'];
        }

        if (isset($parts['fragment'])) {
            $currentUrl .= '#' . $parts['fragment'];
        }

        return $currentUrl;
    }
}
