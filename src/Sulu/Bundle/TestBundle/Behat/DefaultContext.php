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
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;

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
     * Javascript click event.
     *
     * @Given I click ":selector"
     * @When /^(?:|I )click on the element "([^"]*)"$/
     */
    public function iClickOnTheElement($selector)
    {
        $this->clickSelector($selector);
    }

    /**
     * Javascript focus event.
     *
     * @Given I focus ":selector"
     * @When /^(?:|I )focus the element "([^"]*)"$/
     */
    public function iFocusTheElement($selector)
    {
        $this->focusSelector($selector);
    }

    /**
     * Real click event.
     *
     * @Given I click element :selector
     *
     * @param string $selector
     *
     * @throws ElementNotFoundException
     */
    public function click($selector)
    {
        $page = $this->getSession()->getPage();
        $link = $page->find('css', $selector);

        if (null === $link) {
            throw new ElementNotFoundException($this->getSession(), null, 'css', $selector);
        }

        $link->click();
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
     * @And I expect to see ":text"
     * @Given I wait to see ":text"
     */
    public function iExpectToSee($text)
    {
        $this->spin(function () use ($text) {
            $this->waitForTextAndAssert($text);

            return true;
        });
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
     * Checks, that (?P<num>\d+) CSS elements exist on the page
     * Example: Then I wait and should see 5 "div" elements
     * Example: And I wait and should see 5 "div" elements.
     *
     * @Then /^(?:|I )wait and should see (?P<num>\d+) "(?P<element>[^"]*)" elements?$/
     *
     * @param int $num
     * @param string $element
     */
    public function iWaitAndShouldSeeNbElements($num, $element)
    {
        $this->spin(function (RawMinkContext $context) use ($num, $element) {
            $context->assertSession()->elementsCount('css', $element, intval($num));

            return true;
        });
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

    /**
     * @Then I wait and expect to see element :element
     *
     * @param string $selector
     *
     * @throws \Exception
     */
    public function iWaitAndExpectToSeeElement($selector)
    {
        $this->spin(function (RawMinkContext $context) use ($selector) {
            $element = $context->getSession()->getPage()->find('css', $selector);

            if ($element === null) {
                throw new \Exception('Element not found');
            }

            if (!$element->isVisible()) {
                throw new \Exception('Element not visible');
            }

            return true;
        });
    }

    /**
     * Waits and checks, that element with specified CSS contains specified HTML
     * Example: Then the "body" element should contain "style=\"color:black;\""
     * Example: And the "body" element should contain "style=\"color:black;\"".
     *
     * @Then /^(?:|I )wait that the "(?P<element>[^"]*)" element should contain "(?P<value>(?:[^"]|\\")*)"$/
     *
     * @param string $element
     * @param string $value
     */
    public function waitAndAssertElementContains($element, $value)
    {
        $this->spin(function (RawMinkContext $context) use ($element, $value) {
            $context->assertSession()->elementContains('css', $element, $value);

            return true;
        });
    }

    /**
     * Fills in element with specified selector.
     *
     * @When /^(?:|I )fill in element "(?P<selector>(?:[^"]|\\")*)" with "(?P<value>(?:[^"]|\\")*)"$/
     *
     * @param string $selector
     * @param string $value
     *
     * @throws ElementNotFoundException
     */
    public function iFillElement($selector, $value)
    {
        $this->fillElement($selector, $value);
    }

    /**
     * Waits and checks, that current page PATH matches regular expression.
     *
     * @Then /^wait that the (?i)url(?-i) should match (?P<pattern>"(?:[^"]|\\")*")$/
     *
     * @param string $pattern
     */
    public function assertUrlRegExp($pattern)
    {
        $this->spin(function (RawMinkContext $context) use ($pattern) {
            $context->assertSession()->addressMatches($pattern);

            return true;
        });
    }
}
