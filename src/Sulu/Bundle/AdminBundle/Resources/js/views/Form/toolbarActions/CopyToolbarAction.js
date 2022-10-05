// @flow
import React from 'react';
import {action, observable} from 'mobx';
import jexl from 'jexl';
import Dialog from '../../../components/Dialog';
import ResourceRequester from '../../../services/ResourceRequester';
import {translate} from '../../../utils/Translator';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class CopyToolbarAction extends AbstractFormToolbarAction {
    @observable showCopyDialog = false;
    @observable copying = false;

    getNode() {
        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.copying}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_admin.copy"
                onCancel={this.handleCopyDialogClose}
                onConfirm={this.handleCopyDialogConfirm}
                open={this.showCopyDialog}
                title={translate('sulu_admin.create_copy')}
            >
                {translate('sulu_admin.copy_dialog_description')}
            </Dialog>
        );
    }

    getToolbarItemConfig() {
        const {
            visible_condition: visibleCondition,
        } = this.options;

        const {id} = this.resourceFormStore;
        const visibleConditionFulfilled = !visibleCondition || jexl.evalSync(visibleCondition, this.conditionData);

        if (visibleConditionFulfilled) {
            return {
                disabled: !id,
                icon: 'su-copy',
                label: translate('sulu_admin.create_copy'),
                onClick: action(() => {
                    this.showCopyDialog = true;
                }),
                type: 'button',
            };
        }
    }

    @action handleCopyDialogConfirm = () => {
        const {
            id,
            options: {
                webspace,
            },
            resourceKey,
        } = this.resourceFormStore;

        this.copying = true;

        ResourceRequester.post(
            resourceKey,
            undefined,
            {
                action: 'copy',
                id,
                webspace,
            }
        ).then(action((response) => {
            this.copying = false;
            this.showCopyDialog = false;
            this.form.showSuccessSnackbar();

            const {id, webspace} = response;
            this.router.navigate(this.router.route.name, {id, webspace});
        }));
    };

    @action handleCopyDialogClose = () => {
        this.showCopyDialog = false;
    };
}
