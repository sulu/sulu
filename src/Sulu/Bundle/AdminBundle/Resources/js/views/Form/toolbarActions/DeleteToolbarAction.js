// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import jexl from 'jexl';
import Dialog from '../../../components/Dialog';
import {translate} from '../../../utils/Translator';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class DeleteToolbarAction extends AbstractFormToolbarAction {
    @observable showDialog: boolean = false;
    @observable showLinkedDialog: boolean = false;
    @observable referencingItems: Array<Object> = [];

    getNode() {
        return (
            <Fragment key="sulu_admin.delete">
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.resourceFormStore.deleting}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleCancel}
                    onConfirm={this.handleConfirm}
                    open={this.showDialog}
                    title={translate('sulu_admin.delete_warning_title')}
                >
                    {translate('sulu_admin.delete_warning_text')}
                </Dialog>
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.resourceFormStore.deleting}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleLinkCancel}
                    onConfirm={this.handleLinkConfirm}
                    open={this.showLinkedDialog}
                    title={translate('sulu_admin.delete_linked_warning_title')}
                >
                    {translate('sulu_admin.delete_linked_warning_text')}
                    <ul>
                        {this.referencingItems.map((referencingItem, index) => (
                            <li key={index}>{referencingItem.name}</li>
                        ))}
                    </ul>
                </Dialog>
            </Fragment>
        );
    }

    getToolbarItemConfig() {
        const {
            display_condition: displayCondition,
        } = this.options;

        if (displayCondition && !jexl.evalSync(displayCondition, this.resourceFormStore.data)) {
            return;
        }

        return {
            disabled: !this.resourceFormStore.id,
            icon: 'su-trash-alt',
            label: translate('sulu_admin.delete'),
            onClick: action(() => {
                this.showDialog = true;
            }),
            type: 'button',
        };
    }

    navigateBack = () => {
        const {backView} = this.router.route.options;
        const {locale} = this.resourceFormStore;
        this.router.restore(backView, {locale: locale ? locale.get() : undefined});
    };

    @action handleCancel = () => {
        this.showDialog = false;
    };

    @action handleConfirm = () => {
        this.resourceFormStore.delete()
            .then(action(() => {
                this.showDialog = false;
                this.navigateBack();
            }))
            .catch(action((response) => {
                if (response.status !== 409) {
                    throw response;
                }

                this.showDialog = false;
                this.showLinkedDialog = true;
                response.json().then(action((data) => {
                    this.referencingItems.splice(0, this.referencingItems.length);
                    this.referencingItems.push(...data.items);
                }));
            }));
    };

    @action handleLinkCancel = () => {
        this.showLinkedDialog = false;
    };

    @action handleLinkConfirm = () => {
        this.resourceFormStore.delete({force: true})
            .then(action(() => {
                this.showLinkedDialog = false;
                this.navigateBack();
            }));
    };
}
