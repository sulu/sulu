// @flow
import React, {Fragment} from 'react';
import {isArrayLike} from 'mobx';
import {ResourceFormStore} from '../../../containers/Form';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import formToolbarActionRegistry from '../registries/formToolbarActionRegistry';
import Form from '../Form';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';
import type {DropdownOption} from '../../../components/Toolbar/types';
import type {ToolbarItemConfig, DropdownItemConfig} from '../../../containers/Toolbar/types';

export default class DropdownToolbarAction extends AbstractFormToolbarAction {
    toolbarActions: Array<AbstractFormToolbarAction> = [];

    constructor(
        resourceFormStore: ResourceFormStore,
        form: Form,
        router: Router,
        locales: ?Array<string>,
        options: {[key: string]: mixed},
        parentResourceStore: ResourceStore
    ) {
        super(
            resourceFormStore,
            form,
            router,
            locales,
            options,
            parentResourceStore
        );

        const {toolbarActions} = this.options;

        if (!isArrayLike(toolbarActions)) {
            throw new Error('The passed "toolbarActions" option must be of type object or array');
        }

        // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
        this.toolbarActions = toolbarActions.map(
            (action) => {
                if (action === null || typeof action !== 'object') {
                    throw new Error('The passed entries in the "actions" option must be objects');
                }

                const {type, options} = action;

                if (typeof type !== 'string') {
                    throw new Error('The "type" of each entry in the "actions" options must be a string');
                }

                if (options === null || typeof options !== 'object') {
                    throw new Error('The "options" of each entry in the "actions" options must be a string');
                }

                return new (formToolbarActionRegistry.get(type))(
                    this.resourceFormStore,
                    this.form,
                    router,
                    this.locales,
                    ((options: any): {[key: string]: mixed}),
                    parentResourceStore
                );
            });
    }

    getNode(index: ?number) {
        return (
            <Fragment key={'sulu_admin.dropdown' + (index || '')}>
                {this.toolbarActions.map((toolbarAction, index) => toolbarAction.getNode(index))}
            </Fragment>
        );
    }

    getToolbarItemConfig(): ?DropdownItemConfig {
        const {icon, label} = this.options;

        if (typeof label !== 'string') {
            throw new Error('The "label" option must be a string!');
        }

        if (typeof icon !== 'string') {
            throw new Error('The "icon" option must be a string!');
        }

        // use "Boolean" to filter undefined: https://github.com/facebook/flow/issues/1414#issuecomment-251422935
        const childToolbarItemConfigs: Array<ToolbarItemConfig<*>> = this.toolbarActions
            .map((toolbarAction) => toolbarAction.getToolbarItemConfig())
            .filter(Boolean);

        if (childToolbarItemConfigs.length === 0) {
            return undefined;
        }

        const options: Array<DropdownOption> = childToolbarItemConfigs.map((toolbarItemConfig) => {
            if (toolbarItemConfig.options) {
                throw new Error('This ToolbarAction only supports child ToolbarActions not being a dropdown');
            }

            const {disabled, label, onClick} = toolbarItemConfig;

            if (!label) {
                throw new Error('Child ToolbarActions must return a "label"');
            }

            if (!onClick) {
                throw new Error('Child ToolbarActions must return a "onClick" handler');
            }

            return {disabled, label, onClick};
        });

        const loading = childToolbarItemConfigs.some((toolbarItemConfig) => toolbarItemConfig.loading);

        return {
            type: 'dropdown',
            label,
            icon,
            loading,
            options,
        };
    }
}
