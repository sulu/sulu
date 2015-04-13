<?php

namespace Sulu\Component\Content\Document;

/**
 * Constats class for localization states
 */
class LocalizationState
{
    /**
     * Document is loaded in requested locale
     */
    const LOCALIZED = 'localized';

    /**
     * Document is using a fallback
     */
    const GHOST = 'ghost';

    /**
     * Document is using the content of a different localization
     */
    const SHADOW = 'shadow';
}
