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
     * @param string $selector Container element of the field
     *
     * @throws ElementNotFoundException
     * @throws \Excpetion
     */
    public function iSelectFromTheHuskyAutoComplete($itemValue, $selector)
    {
        $containerElement = $this->getSession()->getPage()->find('css', $selector);

        if (null === $containerElement) {
            throw new ElementNotFoundException($this->getSession(), $selector);
        }

        $inputElement = $containerElement->find('css', 'input.tt-input');

        if (null === $inputElement) {
            throw new ElementNotFoundException($this->getSession(), $inputElement);
        }

        // Workaround for $element->setValue() because this function adds a TAB key at the end.
        // See https://github.com/minkphp/MinkSelenium2Driver/issues/188 for more information.
        $el = $this->getSession()->getDriver()->getWebDriverSession()->element('xpath', $inputElement->getXpath());
        $el->postValue(array('value' => array($itemValue)));

        // Wait until loading is finished.
        $this->spin(function (RawMinkContext $context) use ($containerElement) {
            // Search for all displayed suggestions.
            $suggestionElementSelector = '.tt-suggestions .suggestion';
            $suggestionElements = $containerElement->findAll('css', $suggestionElementSelector);

            if (null === $suggestionElements) {
                throw new ElementNotFoundException($this->getSession(), $suggestionElementSelector);
            }

            // Choose the first item in the list.
            $suggestionElements[0]->click();

            $loaderElementSelector = '.loader';
            $loaderElement = $containerElement->find('css', $loaderElementSelector);

            if ($loaderElement && $loaderElement->isVisible()) {
                throw new \Exception('Loader visible');
            }

            return true;
        });
    }
}
