<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Behat;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Sulu\Bundle\TestBundle\Behat\BaseContext;

/**
 * Behat context class for the HuskyBundle.
 */
class HuskyContext extends BaseContext implements SnippetAcceptingContext
{
    /**
     * Select a value from husky select list.
     *
     * @Given I select :itemValue from the husky auto complete :selector
     *
     * @param string $itemValue
     * @param string $htmlId
     *
     * @throws ElementNotFoundException
     * @throws \Excpetion
     */
    public function iSelectFromTheHuskyAutoComplete($itemValue, $htmlId)
    {
        $element = $this->getSession()->getPage()->findById($htmlId);

        if (null === $element) {
            throw new ElementNotFoundException($this->getSession(), $htmlId);
        }

        // Workaround for $element->setValue() because this function adds a TAB key at the end.
        // See https://github.com/minkphp/MinkSelenium2Driver/issues/188 for more information.
        $el = $this->getSession()->getDriver()->getWebDriverSession()->element('xpath', $element->getXpath());
        $el->postValue(array('value' => array($itemValue)));

        // Wait until loading is finished.
        $this->spin(function (RawMinkContext $context) use ($htmlId) {
            // Search for all displayed suggestions.
            $suggestionElementSelector = '#' . $htmlId . 'Field .tt-suggestions .suggestion';
            $suggestionElements = $context->getSession()->getPage()->findAll('css', $suggestionElementSelector);

            if (null === $suggestionElements) {
                throw new ElementNotFoundException($this->getSession(), $suggestionElementSelector);
            }

            // Choose the first item in the list.
            $suggestionElements[0]->click();

            $loaderElementSelector = '#' . $htmlId . 'Field .loader';
            $loaderElement = $context->getSession()->getPage()->find('css', $loaderElementSelector);

            if (null === $loaderElement) {
                throw new ElementNotFoundException($this->getSession(), $loaderElementSelector);
            }

            if ($loaderElement->isVisible()) {
                throw new \Exception('Loader visible');
            }

            return true;
        });
    }
}
