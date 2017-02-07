<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Behat;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Sulu\Bundle\TestBundle\Behat\BaseContext;
use WebDriver\Exception;

/**
 * Behat context class for the AdminBundle.
 */
class AdminContext extends BaseContext implements SnippetAcceptingContext
{
    /**
     * @Then I expect a success notification to appear
     */
    public function iExpectASuccessNotificationToAppear()
    {
        $this->waitForSelectorAndAssert('.husky-label-success-icon', BaseContext::LONG_WAIT_TIME);
    }

    /**
     * @Given I expect a data grid to appear
     * @Given I wait for a data grid to appear
     */
    public function iExpectADataGridToAppear()
    {
        $this->waitForSelectorAndAssert('.husky-datagrid');
    }

    /**
     * @Given I expect a form to appear
     */
    public function iExpectAFormToAppear()
    {
        $this->waitForSelectorAndAssert('form');
    }

    /**
     * @Then I expect a confirmation dialog to appear
     */
    public function iExpectAConfirmationDialogShouldAppear()
    {
        $this->iWaitForAOverlayToAppear();
    }

    /**
     * @Given I click the back icon
     */
    public function iClickOnTheBackIcon()
    {
        $this->clickSelector('.fa-chevron-left');
    }

    /**
     * @Given I click the edit icon
     */
    public function iClickOnTheEditIcon()
    {
        $this->clickSelector('.fa-pencil');
    }

    /**
     * @Given I click the trash icon
     */
    public function iClickOnTheTrashIcon()
    {
        $this->clickSelector('.fa-trash-o');
    }

    /**
     * @Given I click the gears icon
     */
    public function iClickOnTheGearsIcon()
    {
        $this->clickSelector('.fa-gears');
    }

    /**
     * @Given I click the link icon
     */
    public function iClickOnTheLinkIcon()
    {
        $this->clickSelector('.fa-link');
    }

    /**
     * @Given I click the ok button
     */
    public function iClickOnTheOkButton()
    {
        $this->clickSelector('.btn.overlay-ok');
    }

    /**
     * @Given I click the search icon
     */
    public function iClickOnTheSearchButton()
    {
        $this->clickSelector('.btn .fa-search');
    }

    /**
     * @Given I click the close icon
     * @Given I click the close icon in container ":selector"
     */
    public function iClickOnTheCloseIcon($selector = '')
    {
        $this->clickSelector($selector . ' .fa-times');
    }

    /**
     * @Then I click the add icon
     */
    public function iClickOnTheAddIcon()
    {
        $this->clickSelector('.fa-plus-circle');
    }

    /**
     * @Then I click the action icon
     */
    public function iClickOnTheActionIcon()
    {
        $this->clickSelector('.action');
    }

    /**
     * @Given I click the row containing ":text"
     */
    public function iClickOnTheRowContaining($text)
    {
        $this->waitForText($text);
        $script = <<<'EOT'
var f = function () {
    var items = document.querySelectorAll("td span.cell-content");

    for (var i = 0; i < items.length; i++) {
        if (items[i].textContent == '%s') {
            items[i].click();
            break;
        }
    };
}

f();
EOT;

        $script = sprintf($script, $text);
        $this->getSession()->executeScript($script);
    }

    /**
     * @Given I click the edit icon in the row containing ":text"
     */
    public function iClickOnTheEditIconInTheRowContaining($text)
    {
        $this->waitForText($text);
        $script = <<<'EOT'
            var f = function () {
                var items = document.querySelectorAll("td span.cell-content");

                for (var i = 0; i < items.length; i++) {
                    if (items[i].textContent == '%s') {
                        var elements = items[i].parentNode.parentNode.getElementsByClassName('fa-pencil');
                        for (var i = 0; i <= elements.length; i++) {
                            elements[i].click();
                            return;
                        }
                    }
                };
            }

            f();
EOT;

        $script = sprintf($script, $text);
        $this->getSession()->executeScript($script);
    }

    /**
     * @Given I confirm
     */
    public function iConfirm()
    {
        $this->clickSelector('.overlay-ok');
    }

    /**
     * @Given I click :button from the drop down
     */
    public function iClick($button)
    {
        $script = "$(\"li[data-id='" . $button . "']\")";

        $this->waitForAuraEvents(
            [
                'husky.toolbar.header.item.show',
            ]
        );

        $this->getSession()->executeScript($script . '.click();');
    }

    /**
     * @Given I click toolbar item ":id"
     */
    public function iClickToolbarItem($id)
    {
        $script = "$(\"li[data-id='" . $id . "']\")";

        $this->waitForAuraEvents(
            [
                'husky.toolbar.header.item.show',
            ]
        );

        $this->getSession()->executeScript($script . '.click();');
    }

    /**
     * @Then I click the save icon
     */
    public function iClickOnTheSaveIcon()
    {
        $this->clickSelector('.fa-floppy-o');
    }

    /**
     * Select a value from husky select list.
     *
     * @Given I select :itemValue from the husky :selectListClass
     */
    public function iSelectFromTheHusky($itemValue, $selectListClass)
    {
        $script = <<<'EOT'
var selector = '%s';
var items = $("div." + selector + " .husky-select-list .item-value");
if (items.length == 0) {
    var items = $("#" + selector + " .husky-select-list .item-value");
}
for (var i = 0; i < items.length; i++) {
    if (items[i].textContent == '%s') {
        items[i].parentNode.click();
    }
};
EOT;

        $script = sprintf($script, $selectListClass, $itemValue);
        $this->getSession()->executeScript($script);
    }

    /**
     * Fill in a husky text field.
     *
     * @Given I fill in husky field :name with :value
     */
    public function iFillTheHuskyField($name, $value)
    {
        $this->fillInHuskyField($name, $value);
    }

    /**
     * @Given I fill in husky field :name with :value in the overlay
     */
    public function iFillTheHuskyFieldInTheOverlay($name, $value)
    {
        $this->fillInHuskyField($name, $value, '.husky-overlay-container ');
    }

    /**
     * @Then I click the ":text" button
     */
    public function iClickTheButton($text)
    {
        $this->clickByTitle('.btn', $text);
    }

    /**
     * @Then I click the ":id" navigation item
     */
    public function iClickTheNavigationItem($id)
    {
        $this->clickSelector('#' . $id);
    }

    /**
     * @Then I click the overlay tab ":title"
     */
    public function iClickTheOverlayTab($title)
    {
        $this->clickByTitle('.overlay-tabs .tabs-container ul li a', $title);
    }

    /**
     * @Then I click the column navigation item :itemTitle
     */
    public function iClickTheColumnNavigationItem($itemTitle)
    {
        $this->clickByTitle('.column-navigation .item-text', $itemTitle);
    }

    /**
     * @Then I click the toolbar button :itemTitle
     */
    public function iClickTheToolbarButton($itemTitle)
    {
        $this->clickByTitle('li.toolbar-item', $itemTitle);
    }

    /**
     * @When I click the tab item :tabTitle
     */
    public function iClickTheTabItem($tabTitle)
    {
        $selector = '.tabs-container ul li';
        $tabItems = $this->getSession()->getPage()->findAll('css', $selector);

        /* @var NodeElement $tabItem */
        foreach ($tabItems as $tabItem) {
            $element = $tabItem->find('named', ['content', $tabTitle]);
            if ($element && $element->isVisible()) {
                // Click and wait for the ajax request.
                $element->click();
                $this->waitForAjax(self::MEDIUM_WAIT_TIME);

                return;
            }
        }

        throw new ElementNotFoundException($this->getSession(), null, 'css', $selector);
    }

    /**
     * @Then I wait for the column navigation column :index
     */
    public function iWaitForTheColumnNavigationColumn($index)
    {
        $this->waitForSelectorAndAssert('.column-navigation .column[data-column=\'' . $index . '\']');
    }

    /**
     * @Then I double click the column navigation item :itemTitle
     */
    public function iDoubleClickTheColumnNavigationItem($itemTitle)
    {
        $this->clickByTitle('.column-navigation .item-text', $itemTitle, 'dblclick');
    }

    /**
     * @Then I double click the data grid item :itemTitle
     */
    public function iDoubleClickTheDataGridItem($itemTitle)
    {
        $this->clickByTitle('.datagrid-container .item .title', $itemTitle, 'dblclick');
    }

    /**
     * Expect until all of the named events have been fired.
     *
     * @Then I expect the following events:
     */
    public function iExpectTheFollowingEvents(PyStringNode $eventNames)
    {
        $this->waitForAuraEvents($eventNames->getStrings(), 5000);
    }

    /**
     * @Then I expect the ":eventName" event
     */
    public function iExpectTheEvent($eventName)
    {
        $this->waitForAuraEvents([$eventName]);
    }

    /**
     * @Then I wait a second for the ":eventName" event
     */
    public function iWaitASecondForTheEvent($eventName)
    {
        $this->waitForAuraEvents([$eventName], 1000);
    }

    /**
     * @Then there should be :expectedErrorCount form errors
     */
    public function thereShouldBeErrors($expectedErrorCount)
    {
        $errorCount = $this->getSession()->evaluateScript("$('.husky-validate-error').length");
        if ($errorCount != $expectedErrorCount) {
            throw new \Exception(
                sprintf(
                    'Was expecting "%s" form errors, but got "%s"',
                    $expectedErrorCount,
                    $errorCount
                )
            );
        }
    }

    /**
     * @Given I expect an overlay to appear
     * @Given I wait for an overlay to appear
     */
    public function iWaitForAOverlayToAppear()
    {
        $this->waitForSelectorAndAssert('.husky-overlay-container');
    }

    /**
     * @Given I expect the aura component ":name" to appear
     */
    public function iExpectTheAuraComponentToAppear($name)
    {
        $selector1 = 'div[data-instance-name=\\"' . $name . '\\"]';
        $selector2 = 'div[data-aura-instance-name=\\"' . $name . '\\"]';
        $this->getSession()->wait(
            self::LONG_WAIT_TIME,
            sprintf(
                '$(\'%s\').children().length > 0 || $(\'%s\').children().length > 0',
                $selector1,
                $selector2
            )
        );
        $this->assertAtLeastOneSelectors([$selector1, $selector2]);
    }

    /**
     * @Then I set the value of the property ":name" to ":value"
     */
    public function iSetValue($name, $value)
    {
        $this->getSession()->evaluateScript("$('#$name').data('element').setValue(" . json_encode($value) . ')');
    }

    /**
     * @Then I expect the value of the property ":name" is ":value"
     */
    public function IExpectTheValue($name, $value)
    {
        $result = $this->getSession()->evaluateScript(
            "$('#$name').data('element').getValue() === " . json_encode($value)
        );

        if (!$result) {
            throw new \Exception(sprintf('Property "%s" doesnt contain the value "%s"', $name, json_encode($value)));
        }
    }

    /**
     * @Given I expect the toolbar item ":id" to be hidden
     */
    public function iExpectTheToolbarItemToBeHidden($id)
    {
        $script = "li[data-id='" . $id . "']";

        $this->assertSelectorIsHidden($script);
    }

    /**
     * @Given I wait until toolbar dropdown menu is visible and select item :item
     *
     * @param string $item
     */
    public function iWaitUntilToolbarDropdownMenuIsVisible($item)
    {
        $this->spin(function (RawMinkContext $context) use ($item) {
            $page = $context->getSession()->getPage();
            $element = $page->find('css', '.toolbar-dropdown-menu');

            if (null === $element) {
                throw new ElementNotFoundException($this->getSession(), null, 'css', '.toolbar-dropdown-menu');
            }

            if (!$element->isVisible()) {
                return false;
            }

            $item = $element->find('css', 'li[data-id=' . $item . ']');

            if (null === $item) {
                throw new ElementNotFoundException($this->getSession(), null, 'css', 'li[data-id=' . $item . ']');
            }

            $item->click();

            return true;
        });
    }

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
        $el->postValue(['value' => [$itemValue]]);

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
                return false;
            }

            return true;
        });
    }

    /**
     * Fill in the named husky field. Husky fields may not use standard HTML
     * inputs, so they need some special handling.
     *
     * @param string $name Name of field to fill in
     * @param string $value Value to fill in
     * @param string $parentSelector Optional parent selector
     */
    private function fillInHuskyField($name, $value, $parentSelector = '')
    {
        foreach ([
                     'data-aura-instance-name',
                     'data-mapper-property',
                 ] as $propertyName) {
            $script = <<<'EOT'
var el = $('%s[%s="%s"]').data('element');

if (el !== null) {
    el.setValue('%s');
} else {
    throw "Could not find element";
}
EOT;

            $script = sprintf($script, $parentSelector, $propertyName, $name, $value);
            try {
                $this->getSession()->executeScript($script);

                return;
            } catch (Exception $e) {
                // catch wrapped javascript exception, could not find element
                // lets try again..
            }
        }

        throw new \InvalidArgumentException(sprintf('Could not find husky field "%s"', $name));
    }
}
