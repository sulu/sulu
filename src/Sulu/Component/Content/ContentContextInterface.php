<?php

namespace Sulu\Component\Content;

/**
 * The content context holds configuration properties for the
 * current request. Note that these properties may be overridden
 * by the request. This is why this is a Context and not Config.
 */
interface ContentContextInterface
{
    public function getPropertyPrefix();

    public function getLanguageNamespace();

    public function getLanguageDefault();

    public function getTemplateDefault();
}
