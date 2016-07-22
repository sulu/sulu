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

use Behat\Behat\Context\Context;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Base context extended by all Sulu Behat contexts
 * Note this context does not and should not contain any specifications.
 * It is the base class of all Contexts.
 */
abstract class BaseContext extends RawMinkContext implements Context, KernelAwareContext
{
    const LONG_WAIT_TIME = 30000;
    const MEDIUM_WAIT_TIME = 5000;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * {@inheritdoc}
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Return the user ID.
     *
     * This currently could be any integer I believe
     *
     * @return int
     */
    protected function getUserId()
    {
        return 1;
    }

    /**
     * Execute a symfony command.
     *
     * $this->executeCommand('sulu:security:user:create', array(
     *     'firstName' => 'foo',
     *     '--option' => 'bar',
     * ));
     *
     * @param string $command Command to execute
     * @param array  $args    Arguments and options
     *
     * @return int Exit code of command
     */
    protected function execCommand($command, $args)
    {
        $kernel = $this->kernel;

        array_unshift($args, $command);
        $input = new ArrayInput($args);

        $application = new Application($kernel);
        $application->all();

        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $command = $application->find($command);

        $output = new StreamOutput(fopen('php://memory', 'w', false));
        $exitCode = $application->run($input, $output);

        if ($exitCode !== 0) {
            rewind($output->getStream());
            $output = stream_get_contents($output->getStream());

            throw new \Exception(sprintf(
                'Command in BaseContext exited with code "%s": "%s"',
                $exitCode, $output
            ));
        }

        return $exitCode;
    }

    /**
     * Get entity manager.
     *
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->getService('doctrine')->getManager();
    }

    /**
     * Return the PHPCR session.
     */
    protected function getPhpcrSession()
    {
        return $this->getService('sulu.phpcr.session')->getSession();
    }

    /**
     * Returns Container instance.
     *
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->kernel->getContainer();
    }

    /**
     * Return the named service from the DI container.
     *
     * @return mixed
     */
    protected function getService($serviceId)
    {
        return $this->getContainer()->get($serviceId);
    }

    /**
     * Click the named selector.
     *
     * @param string $selector
     */
    protected function clickSelector($selector)
    {
        $this->waitForSelectorAndAssert($selector);
        $script = '$("' . $selector . '").click();';
        $this->getSession()->executeScript($script);
    }

    /**
     * Focus the named selector.
     *
     * @param string $selector
     */
    protected function focusSelector($selector)
    {
        $this->waitForSelectorAndAssert($selector);
        $script = '$("' . $selector . '").focus();';
        $this->getSession()->executeScript($script);
    }

    /**
     * Return the script for clicking by title.
     *
     * @param string $selector  in which the target text should be found
     * @param string $itemTitle Title of text to click within the selector
     * @param string $type      Type of click (i.e. click or dblclick)
     *
     * @return string The script
     */
    protected function clickByTitle($selector, $itemTitle, $type = 'click')
    {
        $script = <<<'EOT'
var f = function () {
    var event = new MouseEvent('%s', {
        'view': window,
        'bubbles': true,
        'cancelable': true
    });

    var items = document.querySelectorAll("%s");

    for (var i = 0; i < items.length; i++) {
        if (items[i].textContent.trim() == '%s') {
            items[i].dispatchEvent(event);
            return;
        }
    };
}

f();
EOT;

        $script = sprintf($script, $type, $selector, $itemTitle);

        $this->getSession()->executeScript($script);
    }

    /**
     * Wait for the named selector to appear.
     *
     * @param string $selector Selector to wait for
     * @param int    $time     Timeout in miliseconds to wait
     */
    protected function waitForSelector($selector, $time = self::LONG_WAIT_TIME)
    {
        $this->getSession()->wait($time, 'document.querySelectorAll("' . $selector . '").length');
    }

    /**
     * Wait for the named selector to appear and produce an
     * error if it has not appeared after the timeout has been
     * exceeded.
     *
     * @param string $selector Selector to wait for
     * @param int    $time     Timeout in miliseconds to wait
     */
    protected function waitForSelectorAndAssert($selector, $time = self::LONG_WAIT_TIME)
    {
        $this->waitForSelector($selector, $time);
        $this->assertSelector($selector);
    }

    /**
     * Wait for the given text to appear.
     *
     * @param string $text
     * @param int    $time Timeout in miliseconds
     */
    protected function waitForText($text, $time = 10000)
    {
        $script = sprintf('$("*:contains(\\"%s\\")").length', $text);
        $this->getSession()->wait($time, $script);
    }

    /**
     * Wait for the given text to ppear and produce an error if it
     * has not appeared after the timeout has been exceeded.
     *
     * @param string $text
     *
     * @throws \Exception
     */
    protected function waitForTextAndAssert($text)
    {
        $this->waitForText($text);
        $script = sprintf('$("*:contains(\\"%s\\")").length', $text);
        $res = $this->getSession()->evaluateScript($script);

        if (!$res) {
            throw new \Exception(sprintf('Page does not contain text "%s"', $text));
        }
    }

    /**
     * Assert that the selector appears the given number of times.
     *
     * @param string $selector
     * @param int    $count    Number of times the selector is expected to appear
     */
    protected function assertNumberOfElements($selector, $count)
    {
        $actual = $this->getSession()->evaluateScript('$("' . $selector . '").length');

        if ($actual != $count) {
            throw new \InvalidArgumentException(sprintf(
                'Expected "%s" items but got "%s"', $count, $actual
            ));
        }
    }

    /**
     * Assert that the given selector is present.
     *
     * @param string $selector
     *
     * @throws \Exception
     */
    protected function assertSelector($selector)
    {
        $res = $this->getSession()->evaluateScript('$("' . $selector . '").length');

        if (!$res) {
            throw new \Exception(sprintf(
                'Failed asserting selector "%s" exists on page',
                $selector
            ));
        }
    }

    /**
     * Assert that the given selector is hidden.
     *
     * @param string $selector
     *
     * @throws \Exception
     */
    protected function assertSelectorIsHidden($selector)
    {
        $res = $this->getSession()->evaluateScript('$("' . $selector . '").css("display") === "none"');
        if (!$res) {
            throw new \Exception(sprintf(
                'Asserting selector "%s" is not hidden on page',
                $selector
            ));
        }
    }

    /**
     * Assert that at least one of the given selectors is present.
     *
     * @param array $selectors Array of selectors
     *
     * @throws \Exception
     */
    protected function assertAtLeastOneSelectors($selectors)
    {
        foreach ($selectors as $selector) {
            try {
                return $this->assertSelector($selector);
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \Exception(sprintf('Could not find any of the selectors: "%s"',
            implode('", "', $selectors)
        ));
    }

    /**
     * Set the value of the named selector.
     *
     * @param string $selector
     * @param mixed  $value
     */
    protected function fillSelector($selector, $value)
    {
        $this->getSession()->executeScript(sprintf(<<<'EOT'
var els = document.querySelectorAll("%s");
for (var i in els) {
    var el = els[i];
    el.value = '%s';
}
EOT
        , $selector, $value));
    }

    /**
     * Wait for the named aura events.
     *
     * @param array $eventNames Array of event names
     * @param int   $time       in milliseconds
     */
    protected function waitForAuraEvents($eventNames, $time = self::MEDIUM_WAIT_TIME)
    {
        $script = [];
        $uniq = uniqid();
        $varNames = [];

        foreach (array_keys($eventNames) as $i) {
            $varName = 'document.__behatvar' . $uniq . $i;
            $varNames[$i] = $varName;
            $script[] = sprintf('%s = false;', $varName);
        }

        foreach ($eventNames as $i => $eventName) {
            $varName = $varNames[$i];
            $script[] = sprintf("app.sandbox.on('%s', function () { %s = true; });",
                $eventName,
                $varName
            );
            $script[] = 'console.log("' . $eventName . '");';
        }

        $script = implode("\n", $script);
        $assertion = implode(' && ', $varNames);

        $this->getSession()->executeScript($script);
        $this->getSession()->wait($time, $assertion);
    }

    /**
     * Using spin functions for slow tests.
     *
     * @param callable $lambda
     * @param int $wait
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected function spin($lambda, $wait = 5)
    {
        for ($i = 0; $i < $wait; ++$i) {
            try {
                if ($lambda($this)) {
                    return true;
                }
            } catch (\Exception $e) {
                // Ignore exception & do nothing.
            }

            sleep(1);
        }

        $backtrace = debug_backtrace();

        throw new \Exception('Timeout thrown by ' . $backtrace[1]['class'] . '::' . $backtrace[1]['function']);
    }

    /**
     * Fills in element with specified selector.
     *
     * @param string $selector
     * @param string $value
     *
     * @throws ElementNotFoundException
     */
    protected function fillElement($selector, $value)
    {
        $page = $this->getSession()->getPage();
        $element = $page->find('css', $selector);

        if (null === $element) {
            throw new ElementNotFoundException($this->getSession(), null, 'css', $selector);
        }

        $element->setValue($value);
    }

    /**
     * Wait until ajax requests are terminated.
     *
     * @param int $timeout timeout in milliseconds
     */
    protected function waitForAjax($timeout)
    {
        $this->getSession()->wait($timeout, '(0 === jQuery.active)');
    }
}
