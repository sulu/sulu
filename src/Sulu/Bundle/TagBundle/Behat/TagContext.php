<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Behat;

use Sulu\Bundle\TestBundle\Behat\BaseContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Sulu\Bundle\TagBundle\Entity\Tag;

/**
 * Behat context class for the TagBundle
 */
class TagContext extends BaseContext implements SnippetAcceptingContext
{
    /**
     * @Given the following tags exist:
     */
    public function givenTheFollowingTagsExist(TableNode $node)
    {
        foreach ($node as $row) {
            $tag = new Tag();
            $tag->setName($row['name']);
            $tag->setChanged(new \DateTime());
            $tag->setCreated(new \DateTime());
            $this->getEntityManager()->persist($tag);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @Then the tag ":name" should exist
     */
    public function theTagShouldExist($name)
    {
        $tag = $this->getEntityManager()->getRepository('Sulu\Bundle\TagBundle\Entity\Tag')
            ->findOneByName($name);

        if (!$tag) {
            throw new \InvalidArgumentException(sprintf(
                'The tag "%s" should exist, but it does not.',
                $name
            ));
        }
    }
}
