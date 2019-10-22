<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\View;

interface FormViewBuilderInterface extends ViewBuilderInterface
{
    public function setResourceKey(string $resourceKey): self;

    public function setFormKey(string $formKey): self;

    /**
     * @param string[] $locales
     */
    public function addLocales(array $locales): self;

    public function setTabTitle(string $tabTitle): self;

    public function setTabCondition(string $tabCondition): self;

    public function setTabOrder(int $tabOrder): self;

    public function setTabPriority(int $tabPriority): self;

    /**
     * @param ToolbarAction[] $toolbarActions
     */
    public function addToolbarActions(array $toolbarActions): self;

    /**
     * @param string[] $routerAttributesToFormRequest
     */
    public function addRouterAttributesToFormRequest(array $routerAttributesToFormRequest): self;

    /**
     * @param string[] $routerAttributesToEditView
     */
    public function addRouterAttributesToEditView(array $routerAttributesToEditView): self;

    public function setEditView(string $editView): self;

    public function setBackView(string $editView): self;

    public function addMetadataRequestParameters(array $formMetadata): self;

    public function setIdQueryParameter(string $idQueryParameter): self;

    public function setTitleVisible(bool $titleVisible): self;
}
