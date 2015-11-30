<?php
namespace Sulu\Component\Content\Compat;

/**
 * Find best localization.
 */
interface LocalizationFinderInterface
{
    /**
     * @param string $webspaceName
     * @param string[] $availableLocales
     * @param string $locale
     *
     * @return string
     */
    public function findAvailableLocale($webspaceName, array $availableLocales, $locale);
}
