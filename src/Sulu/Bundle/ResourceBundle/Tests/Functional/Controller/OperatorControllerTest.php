<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ResourceBundle\Entity\Operator;
use Sulu\Bundle\ResourceBundle\Entity\OperatorTranslation;
use Sulu\Bundle\ResourceBundle\Entity\OperatorValue;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\HttpKernel\Client;

class OperatorControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Operator
     */
    protected $op2;

    /**
     * @var Operator
     */
    protected $op1;

    protected function setUp()
    {
        parent::setUp();
        $this->em = $this->db('ORM')->getOm();
        $this->purgeDatabase();
        $this->setUpOperators();
        $this->client = $this->createAuthenticatedClient();
    }

    protected function setUpOperators()
    {

    }

    protected function createSimpleOperator($type, $inputType, $operator, $name)
    {

    }
}
