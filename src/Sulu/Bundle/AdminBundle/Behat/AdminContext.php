<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Behat;

use Sulu\Bundle\TestBundle\Behat\BaseContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use WebDriver\Exception\UnknownError;

/**
 * Behat context class for the AdminBundle
 */
class AdminContext extends BaseContext implements SnippetAcceptingContext
{
    /**
     * @Then I expect a success notification to appear
     */
    public function iExpectASuccessNotificationToAppear()
    {
        $this->waitForSelectorAndAssert('.husky-label-success', BaseContext::LONG_WAIT_TIME);
    }

    /**
     * @Given I expect a data grid to appear
     */
    public function iExpectADataGridToAppear()
    {
        $this->waitForSelectorAndAssert('.husky-datagrid .row');
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
        $this->iExpectAnOverlayToAppear();
    }

    /**
     * @Then I expect an overlay to appear
     */
    public function iExpectAnOverlayToAppear()
    {
        $this->getSession()->wait(5000,
            "document.querySelector('.husky-overlay-container')"
        );
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
     * @Given I click the tick button
     */
    public function iClickOnTheTickButton()
    {
        $this->clickSelector('.btn.tick');
    }

    /**
     * @Given I click the search icon
     */
    public function iClickOnTheSearchButton()
    {
        $this->clickSelector('.btn .fa-search');
    }

    /**
     * @Then I click the add icon
     */
    public function iClickOnTheAddIcon()
    {
        $this->clickSelector('.fa-plus-circle');
    }

    /**
     * @Given I click the row containing ":text"
     */
    public function iClickOnTheRowContaining($text)
    {
        $this->waitForText($text);
        $script = <<<EOT
var f = function () {
    var items = document.querySelectorAll("td span.cell-content");

    for (var i = 0; i < items.length; i++) {
        if (items[i].textContent == '%s') {
            items[i].click();
            return;
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
        $script = <<<EOT
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
     * @Given I click delete from the drop down
     */
    public function iClickDelete()
    {
        $script = "$(\"li[data-id='delete-button']\")";

        $this->waitForAuraEvents(array(
            'husky.toolbar.header.item.show'
        ));

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
     * Select a value from husky select list
     * 
     * @Given I select :itemValue from the husky :selectListClass
     */
    public function iSelectFromTheHusky($itemValue, $selectListClass)
    {
        $script = <<<EOT
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
     * Fill in a husky text field
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
     * @Then I click the column navigation item :itemTitle
     */
    public function iClickTheColumnNavigationItem($itemTitle)
    {
        $this->clickByTitle('.column-navigation .item-text', $itemTitle);
    }

    /**
     * @Then I double click the column navigation item :itemTitle
     */
    public function iDoubleClickTheColumnNavigationItem($itemTitle)
    {
        $this->clickByTitle('.column-navigation .item-text', $itemTitle, 'dblclick');
    }

    /**
     * Expect until all of the named events have been fired
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
        $this->waitForAuraEvents(array($eventName));
    }

    /**
     * @Then I wait a second for the ":eventName" event
     */
    public function iWaitASecondForTheEvent($eventName)
    {
        $this->waitForAuraEvents(array($eventName), 1000);
    }

    /**
     * @Then there should be :expectedErrorCount form errors
     */
    public function thereShouldBeErrors($expectedErrorCount)
    {
        $errorCount = $this->getSession()->evaluateScript("$('.husky-validate-error').length");
        if ($errorCount != $expectedErrorCount) {
            throw new \Exception(sprintf(
                'Was expecting "%s" form errors, but got "%s"', $expectedErrorCount, $errorCount
            ));
        }
    }

    /**
     * @Given I expect the aura component ":name" to appear
     */
    public function iExpectTheAuraComponentToAppear($name)
    {
        $selector1 = 'div[data-instance-name=\\"' . $name . '\\"]';
        $selector2 = 'div[data-aura-instance-name=\\"' . $name . '\\"]';
        $this->getSession()->wait(self::LONG_WAIT_TIME, sprintf(
            '$(\'%s\').children().length > 0 || $(\'%s\').children().length > 0',
            $selector1,
            $selector2
        ));
        $this->assertAtLeastOneSelectors(array($selector1, $selector2));
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
        foreach (array(
            'data-aura-instance-name',
            'data-mapper-property'
        ) as $propertyName) {
            $script = <<<EOT
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
            } catch (UnknownError $e) {
                // catch wrapped javascript exception, could not find element
                // lets try again..
            }
        }

        throw new \InvalidArgumentException(sprintf('Could not find husky field "%s"', $name));
    }
}
