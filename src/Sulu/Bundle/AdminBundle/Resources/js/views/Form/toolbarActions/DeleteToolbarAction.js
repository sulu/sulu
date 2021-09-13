// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import jexl from 'jexl';
import log from 'loglevel';
import Dialog from '../../../components/Dialog';
import {translate} from '../../../utils/Translator';
import {ResourceFormStore} from '../../../containers/Form';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import Form from '../Form';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class DeleteToolbarAction extends AbstractFormToolbarAction {
    @observable showDialog: boolean = false;
    @observable showLinkedDialog: boolean = false;
    @observable referencingItems: Array<Object> = [];

    @computed get allowConflictDeletion() {
        const {allow_conflict_deletion: allowConflictDeletion = true} = this.options;

        return allowConflictDeletion;
    }

    constructor(
        resourceFormStore: ResourceFormStore,
        form: Form,
        router: Router,
        locales: ?Array<string>,
        options: { [key: string]: mixed },
        parentResourceStore: ResourceStore
    ) {
        const {
            display_condition: displayCondition,
            visible_condition: visibleCondition,
            delete_locale: deleteLocale = false,
        } = options;

        if (displayCondition) {
            // @deprecated
            log.warn(
                'The "display_condition" option is deprecated since version 2.0 and will be removed. ' +
                'Use the "visible_condition" option instead.'
            );

            if (!visibleCondition) {
                options.visible_condition = displayCondition;
            }
        }

        if (typeof deleteLocale !== 'boolean') {
            throw new Error('The "delete_locale" option must be a boolean, but received ' + typeof deleteLocale + '!');
        }

        super(resourceFormStore, form, router, locales, options, parentResourceStore);
    }

    getNode() {
        const {delete_locale: deleteLocale = false} = this.options;
        const postfix = deleteLocale ? '_locale' : '';

        return (
            <Fragment key={'sulu_admin.delete' + postfix}>
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.resourceFormStore.deleting}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleCancel}
                    onConfirm={this.handleConfirm}
                    open={this.showDialog}
                    title={translate('sulu_admin.delete' + postfix + '_warning_title')}
                >
                    {translate('sulu_admin.delete' + postfix + '_warning_text')}
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
            visible_condition: visibleCondition,
            delete_locale: deleteLocale = false,
        } = this.options;

        const {id} = this.resourceFormStore;

        const visibleConditionFulfilled = !visibleCondition || jexl.evalSync(visibleCondition, this.conditionData);
        const isDisabled = !id || (deleteLocale && jexl.evalSync('contentLocales.length == 1', this.conditionData));

        if (visibleConditionFulfilled) {
            return {
                disabled: !!isDisabled,
                icon: 'su-trash-alt',
                label: translate('sulu_admin.delete' + (deleteLocale ? '_locale' : '')),
                onClick: action(() => {
                    this.showDialog = true;
                }),
                type: 'button',
            };
        }
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

                if (typeof attributeKey !== 'string' || typeof attributeName !== 'string') {
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
        const {delete_locale: deleteLocale = false} = this.options;

        this.resourceFormStore.delete({deleteLocale})
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
        const {delete_locale: deleteLocale = false} = this.options;

        if (!this.allowConflictDeletion) {
            this.showLinkedDialog = false;
            return;
        }

        this.resourceFormStore.delete({force: true, deleteLocale})
            .then(action(() => {
                this.showLinkedDialog = false;
                this.navigateBack();
            }));
    };
}
