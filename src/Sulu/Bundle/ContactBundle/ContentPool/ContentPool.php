<?php

namespace Sulu\Bundle\ContactBundle\Admin;

// TODO: this file does not belong here
/*
 * possible solutions:
 * 1. extend interface of sulu core bundle
 * 2. make this part of adminbundle -> contentnavigation
 */
class ContentPool {

    private $contents;

    public function __construct() {
        $this->contents = array();
    }

    public function addContent($content) {
        $this->contents[] = $content;
    }

    public function getContents() {
        return $this->contents;
    }
}