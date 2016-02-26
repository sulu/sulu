<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Behat;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * Default context class for Sulu contexts.
 */
class DefaultContext extends BaseContext implements SnippetAcceptingContext
{
    /**
     * @BeforeScenario
     */
    public function initEnv(BeforeScenarioScope $scope)
    {
        $this->execCommand('doctrine:fixtures:load', ['--no-interaction' => true, '--append' => false]);
        $this->execCommand('sulu:document:initialize', [
            '--purge' => true,
            '--force' => true,
        ]);
    }

    /**
     * @Given I click ":selector"
     * @When /^(?:|I )click on the element "([^"]*)"$/
     */
    public function iClickOnTheElement($selector)
    {
        $this->clickSelector($selector);
    }

    /**
     * @Given I click ":selector" in ":containerSelector"
     */
    public function iClickOnTheIn($selector, $containerSelector)
    {
        $session = $this->getSession(); // get the mink session
        $element = $session->getPage()->find('css', sprintf('%s a:contains("%s")', $containerSelector, $selector)); // runs the actual query and returns the element

        // errors must not pass silently
        if (null === $element) {
            throw new \InvalidArgumentException(sprintf('Could not find link for: "%s"', $selector));
        }

        // ok, let's click on it
        $element->click();
    }

    /**
     * @Given pause
     * @Given I pause
     */
    public function pause()
    {
        while (true) {
            sleep(5);
        }
    }

    /**
     * @Given I wait a second
     * @Given wait a second
     */
    public function waitASecond()
    {
        sleep(1);
    }

    /**
     * @Then switch to main window
     */
    public function switchToMainWindow()
    {
        $this->getSession()->switchToWindow(null);
    }

    /**
     * @Then I expect to see ":text"
     * @Given I wait to see ":text"
     */
    public function iExpectToSee($text)
    {
        $this->waitForTextAndAssert($text);
    }

    /**
     * @Then I expect to see ":count" ":selector" elements
     * @Then I wait to see ":count" ":selector" elements
     */
    public function iExpectToSeeNbElements($count, $selector)
    {
        $this->waitForSelector($selector);
        $this->assertNumberOfElements($selector, $count);
    }

    /**
     * @Given I fill in the selector :selector with :value
     */
    public function iFillTheSelector($selector, $value)
    {
        $this->waitForSelector($selector);
        $this->fillSelector($selector, $value);
    }

    /**
     * @Given I leave the selector :selector
     */
    public function iLeaveTheSelector($selector)
    {
        $this->waitForSelector($selector);
        $this->getSession()->evaluateScript("$('$selector').trigger('focusout')");
    }

    /**
     * @Given I clear and fill in :field with :value
     */
    public function clearAndFill($field, $value)
    {
        $this->getSession()->getPage()->fillField($field, '');
        $this->getSession()->getPage()->fillField($field, $value);
    }

    /**
     * @Given I press enter on ":selector"
     */
    public function iPressEnterOn($selector)
    {
        $this->clickSelector($selector);
        $script = <<<'EOT'
var e = $.Event("keypress");
e.which = 13;
e.keyCode = 13;
$('%s').trigger(e);
EOT;

        $this->getSession()->executeScript(sprintf($script, $selector));
    }

    /**
     * @Then wait for the ajax request
     * @Then I wait for the ajax request
     */
    public function iExpectTheAjaxRequest()
    {
        $active = (int) $this->getSession()->evaluateScript('$.active');

        if ($active === 0) {
            $this->getSession()->wait(1000, '$.active > 0');
        }

        $this->getSession()->wait(BaseContext::LONG_WAIT_TIME, '$.active == 0');
    }
}
