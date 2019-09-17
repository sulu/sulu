// @flow
import React, {Fragment} from 'react';
import {ResourceFormStore} from '../../../containers/Form';
import type {DropdownOption} from '../../../components/Toolbar/types';
import Router from '../../../services/Router';
import formToolbarActionRegistry from '../registries/formToolbarActionRegistry';
import Form from '../Form';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class DropdownToolbarAction extends AbstractFormToolbarAction {
    toolbarActions: Array<AbstractFormToolbarAction> = [];

    constructor(
        resourceFormStore: ResourceFormStore,
        form: Form,
        router: Router,
        locales: ?Array<string>,
        options: {[key: string]: mixed}
    ) {
        super(
            resourceFormStore,
            form,
            router,
            locales,
            options
        );

        const {actions} = this.options;

        if (!Array.isArray(actions)) {
            throw new Error('The passed "actions" option must be of type object or array');
        }

        this.toolbarActions = actions
            .map((action) => {
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
                    ((options: any): {[key: string]: mixed})
                );
            });
    }

    getNode() {
        return (
            // TODO Don't hardcode key to allow multiple usage of this action
            <Fragment key="sulu_admin.dropdown">
                {this.toolbarActions.map((toolbarAction) => toolbarAction.getNode())}
            </Fragment>
        );
    }

    getToolbarItemConfig() {
        const {icon, label} = this.options;

        if (typeof label !== 'string') {
            throw new Error('The "label" option must be a string!');
        }

        if (typeof icon !== 'string') {
            throw new Error('The "label" option must be a string!');
        }

        const options: Array<DropdownOption> = this.toolbarActions
            .reduce((toolbarActions, toolbarAction) => {
                const toolbarItemConfig = toolbarAction.getToolbarItemConfig();

                if (!toolbarItemConfig) {
                    return toolbarActions;
                }

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

                toolbarActions.push({disabled, label, onClick});

                return toolbarActions;
            }, []);

        if (options.length === 0) {
            return undefined;
        }

        return {
            type: 'dropdown',
            label,
            icon,
            options,
        };
    }
}
