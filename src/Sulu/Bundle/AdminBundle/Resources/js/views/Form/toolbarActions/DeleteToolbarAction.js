// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import jexl from 'jexl';
import Dialog from '../../../components/Dialog';
import {translate} from '../../../utils/Translator';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class DeleteToolbarAction extends AbstractFormToolbarAction {
    @observable showDialog: boolean = false;
    @observable showLinkedDialog: boolean = false;
    @observable referencingItems: Array<Object> = [];

    @computed get allowConflictDeletion() {
        const {allow_conflict_deletion: allowConflictDeletion = true} = this.options;

        return allowConflictDeletion;
    }

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
                    onCancel={this.allowConflictDeletion ? this.handleLinkCancel : undefined}
                    onConfirm={this.handleLinkConfirm}
                    open={this.showLinkedDialog}
                    title={this.allowConflictDeletion
                        ? translate('sulu_admin.delete_linked_warning_title')
                        : translate('sulu_admin.item_not_deletable')
                    }
                >
                    {this.allowConflictDeletion
                        ? translate('sulu_admin.delete_linked_warning_text')
                        : translate('sulu_admin.delete_linked_abort_text')
                    }
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
        const {attributes, route} = this.router;
        const {backView} = route.options;
        const {locale} = this.resourceFormStore;

        const {
            router_attributes_to_back_view: routerAttributesToBackView,
        } = this.options;

        const backViewAttributes = {locale: locale ? locale.get() : undefined};
        if (routerAttributesToBackView) {
            if (typeof routerAttributesToBackView !== 'object') {
                throw new Error('The "router_attributes_to_back_view" option must be an object!');
            }

            Object.keys(routerAttributesToBackView).forEach((key) => {
                const attributeKey = routerAttributesToBackView[key];
                const attributeName = isNaN(key) ? key : routerAttributesToBackView[key];

                if (typeof attributeKey !== 'string') {
                    throw new Error('The value of the "router_attributes_to_back_view" option must be a string!');
                }

                backViewAttributes[attributeKey] = attributes[attributeName];
            });
        }

        this.router.restore(backView, backViewAttributes);
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
        if (!this.allowConflictDeletion) {
            this.showLinkedDialog = false;
            return;
        }

        this.resourceFormStore.delete({force: true})
            .then(action(() => {
                this.showLinkedDialog = false;
                this.navigateBack();
            }));
    };
}
