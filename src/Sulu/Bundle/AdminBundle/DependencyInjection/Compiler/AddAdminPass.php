<?php
/**
 * Created by JetBrains PhpStorm.
 * User: danielrotter
 * Date: 19.07.13
 * Time: 14:15
 * To change this template use File | Settings | File Templates.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add all admin-services with the tag "sulu.admin" to the AdminPool-Service
 *
 * @package Sulu\Bundle\AdminBundle\DependencyInjection\Compiler
 */
class AddAdminPass implements CompilerPassInterface {

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {

    }
}