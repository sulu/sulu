<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource\Exception;

/**
 * Exception which is thrown when feature for a context is not enabled
 * Class MissingFeatureException.
 */
class MissingFeatureException extends FilterException
{
    /**
     * @var string
     */
    private $context;

    /**
     * The name of the context not found.
     *
     * @var string
     */
    private $featureName;

    public function __construct($context, $featureName)
    {
        $this->featureName = $featureName;
        $this->context = $context;
        parent::__construct(
            'The feature "' . $featureName . '" is not enabled for the context "' . $context . '""!',
            0
        );
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public function getFeatureName()
    {
        return $this->featureName;
    }
}
