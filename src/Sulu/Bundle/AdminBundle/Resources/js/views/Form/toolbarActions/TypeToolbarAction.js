// @flow
import jexl from 'jexl';
import React from 'react';
import {observable, action} from 'mobx';
import Dialog from '../../../components/Dialog';
import {translate} from '../../../utils';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';
import type {ToolbarItemConfig} from '../../../containers/Toolbar/types';

export default class TypeToolbarAction extends AbstractFormToolbarAction {
    @observable selectedTypeForUnsavedChangesDialog: ?string = undefined;

    getToolbarItemConfig(): ?ToolbarItemConfig<string> {
        const formTypes = Object.keys(this.resourceFormStore.types).map((key) => this.resourceFormStore.types[key]);

        if (!this.resourceFormStore.typesLoading && formTypes.length === 0) {
            throw new Error('The ToolbarAction for types only works with entities actually supporting types!');
        }

        const {
            disabled_condition: disabledCondition,
            sort_by: sortBy,
        } = this.options;

        if (sortBy !== undefined && typeof sortBy !== 'string') {
            throw new Error('The "sort_by" option must be a string if given!');
        }

        const isDisabled = disabledCondition ? jexl.evalSync(disabledCondition, this.resourceFormStore.data) : false;

        const sortedTypes = sortBy
            ? formTypes.sort((t1, t2) => String(t1[sortBy]).localeCompare(String(t2[sortBy])))
            : formTypes;

        return {
            type: 'select',
            icon: 'su-brush',
            onChange: action((value: string | number) => {
                if (typeof value !== 'string') {
                    throw new Error('Only strings are valid as a form type!');
                }

                if (!this.resourceFormStore.dirty) {
                    this.resourceFormStore.changeType(value);
                } else {
                    this.selectedTypeForUnsavedChangesDialog = value;
                }
            }),
            loading: this.resourceFormStore.typesLoading,
            value: this.resourceFormStore.type,
            disabled: isDisabled,
            options: sortedTypes.map((type) => ({
                value: type.key,
                label: type.title,
            })),
        };
    }

    getNode() {
        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_admin.type"
                onCancel={this.handleUnsavedChangesDialogClose}
                onConfirm={this.handleUnsavedChangesDialogConfirm}
                open={!!this.selectedTypeForUnsavedChangesDialog}
                title={translate('sulu_admin.change_type_dirty_warning_dialog_title')}
            >
                {translate('sulu_admin.dirty_warning_dialog_text')}
            </Dialog>
        );
    }

    @action handleUnsavedChangesDialogClose = () => {
        this.selectedTypeForUnsavedChangesDialog = undefined;
    };

    @action handleUnsavedChangesDialogConfirm = () => {
        if (this.selectedTypeForUnsavedChangesDialog) {
            this.resourceFormStore.changeType(this.selectedTypeForUnsavedChangesDialog);
        }

        this.selectedTypeForUnsavedChangesDialog = undefined;
    };
}
