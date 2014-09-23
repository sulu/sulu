<?php

namespace Sulu\Component\Maintenance;

use Sulu\Component\Maintainence\MaintainerInterface;

/**
 * This class manages maintainence classes
 */
class MaintenanceManager
{
    protected $maintainers = array();

    /**
     * Register a maintainer
     *
     * @param MaintainerInterface
     */
    public function registerMaintainer(MaintainerInterface $maintainer)
    {
        if (isset($this->maintainers[$maintainer->getName()])) {
            throw new \InvalidArgumentException(sprintf(
                'There already exists a maintainer with the name "%s" when trying to add maintainer of class "%s"',
                $maintainer->getName(), get_class($maintainer)
            ));
        }

        $this->maintainers[$maintainer->getName()] = $maintainer;
    }

    /**
     * Return all maintainers
     */
    public function getMaintainers()
    {
        return $this->maintainers;
    }

    /**
     * Return maintainer with given name
     */
    public function getMaintainer($name)
    {
        if (!isset($this->maintainers[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Trying to get unknown maintainer with name "%s", known maintainers are "%s"',
                $name, implode(', ', array_keys($this->maintainers))
            ));
        }

        return $this->maintainers[$name];
    }
}
