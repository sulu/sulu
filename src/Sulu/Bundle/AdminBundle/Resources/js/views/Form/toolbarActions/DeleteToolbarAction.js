// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import jexl from 'jexl';
import log from 'loglevel';
import Dialog from '../../../components/Dialog';
import DeleteDependantResourcesDialog from '../../../containers/DeleteDependantResourcesDialog';
import DeleteReferencedResourceDialog from '../../../containers/DeleteReferencedResourceDialog';
import {translate} from '../../../utils';
import {ResourceFormStore} from '../../../containers/Form';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import Form from '../Form';
import {ERROR_CODE_DEPENDANT_RESOURCES_FOUND, ERROR_CODE_REFERENCING_RESOURCES_FOUND} from '../../../constants';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';
import type {DependantResourcesData, ReferencingResourcesData} from '../../../types';

export default class DeleteToolbarAction extends AbstractFormToolbarAction {
    @observable showDialog: boolean = false;
    @observable referencingResourcesData: ?ReferencingResourcesData = undefined;
    @observable dependantResourcesData: ?DependantResourcesData = undefined;

    @computed get allowConflictDeletion(): boolean {
        const {allow_conflict_deletion: allowConflictDeletion = true} = this.options;

        return !!allowConflictDeletion;
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

    handleDeleteReferencedResourcesDialogCancel = () => {
        this.closeDeleteReferencedResourceDialog();
    };

    @action handleDeleteReferencedResourcesDialogConfirm = () => {
        this.delete(true);
    };

    @action closeDeleteReferencedResourceDialog = () => {
        this.referencingResourcesData = undefined;
    };

    renderDeleteReferencedResourceDialog() {
        if (!this.referencingResourcesData) {
            return null;
        }

        return (
            <DeleteReferencedResourceDialog
                allowDeletion={this.allowConflictDeletion}
                confirmLoading={this.resourceFormStore.deleting}
                onCancel={this.handleDeleteReferencedResourcesDialogCancel}
                onConfirm={this.handleDeleteReferencedResourcesDialogConfirm}
                referencingResourcesData={this.referencingResourcesData}
            />
        );
    }

    handleDeleteDependantResourcesDialogFinish = () => {
        this.delete();
    };

    handleDeleteDependantResourcesDialogCancel = () => {
        this.closeDeleteDependantResourcesDialog();
    };

    @action closeDeleteDependantResourcesDialog = () => {
        this.dependantResourcesData = undefined;
    };

    @computed get deleteDependantResourcesDialogRequestOptions() {
        const {locale, options: resourceFormStoreOptions = {}} = this.resourceFormStore;

        const options = resourceFormStoreOptions;

        if (locale) {
            options.locale = locale.get();
        }

        return options;
    }

    renderDeleteDependantResourcesDialog() {
        if (!this.dependantResourcesData) {
            return null;
        }

        return (
            <DeleteDependantResourcesDialog
                dependantResourcesData={this.dependantResourcesData}
                onCancel={this.handleDeleteDependantResourcesDialogCancel}
                onFinish={this.handleDeleteDependantResourcesDialogFinish}
                requestOptions={this.deleteDependantResourcesDialogRequestOptions}
            />
        );
    }

    handleDialogCancel = () => {
        this.closeDialog();
    };

    handleDialogConfirm = () => {
        this.delete();
    };

    @action closeDialog = () => {
        this.showDialog = false;
    };

    renderDialog(postfix: string) {
        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.resourceFormStore.deleting}
                confirmText={translate('sulu_admin.ok')}
                onCancel={this.handleDialogCancel}
                onConfirm={this.handleDialogConfirm}
                open={this.showDialog}
                title={translate('sulu_admin.delete' + postfix + '_warning_title')}
            >
                {translate('sulu_admin.delete' + postfix + '_warning_text')}
            </Dialog>
        );
    }

    getNode() {
        const {delete_locale: deleteLocale = false} = this.options;
        const postfix = deleteLocale ? '_locale' : '';

        return (
            <Fragment key={'sulu_admin.delete' + postfix}>
                {this.renderDialog(postfix)}
                {this.renderDeleteReferencedResourceDialog()}
                {this.renderDeleteDependantResourcesDialog()}
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
        const isDisabled = !id || (deleteLocale && jexl.evalSync(
            'contentLocales && contentLocales|length == 1',
            this.conditionData
        ));

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

    @action delete = (force: boolean = false) => {
        const {delete_locale: deleteLocale = false} = this.options;

        const options: {[string]: any} = {deleteLocale};

        if (force) {
            options.force = true;
        }

        return this.resourceFormStore.delete(options)
            .then(() => {
                this.closeDialog();
                this.closeDeleteDependantResourcesDialog();
                this.closeDeleteReferencedResourceDialog();

                this.navigateBack();
            })
            .catch(action((response) => {
                response.json().then(action((data) => {
                    this.closeDialog();
                    this.closeDeleteDependantResourcesDialog();
                    this.closeDeleteReferencedResourceDialog();

                    if (response.status === 409 && data.code === ERROR_CODE_DEPENDANT_RESOURCES_FOUND) {
                        this.dependantResourcesData = {
                            dependantResourceBatches: data.dependantResourceBatches,
                            dependantResourcesCount: data.dependantResourcesCount,
                            detail: data.detail,
                            title: data.title,
                        };

                        return;
                    }

                    if (response.status === 409 && data.code === ERROR_CODE_REFERENCING_RESOURCES_FOUND) {
                        this.referencingResourcesData = {
                            resource: data.resource,
                            referencingResources: data.referencingResources,
                            referencingResourcesCount: data.referencingResourcesCount,
                        };

                        return;
                    }

                    const error = data.detail || data.title || translate('sulu_admin.unexpected_delete_server_error');

                    if (error) {
                        this.form.errors.push(error);
                    }
                }));
            }));
    };
}
