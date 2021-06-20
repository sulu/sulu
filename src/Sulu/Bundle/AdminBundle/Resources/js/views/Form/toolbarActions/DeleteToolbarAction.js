// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import jexl from 'jexl';
import log from 'loglevel';
import Dialog from '../../../components/Dialog';
import DeleteDependantsDialog from '../../../containers/DeleteDependantsDialog';
import DeleteReferencedResourceDialog from '../../../containers/DeleteReferencedResourceDialog';
import {translate} from '../../../utils';
import {ResourceFormStore} from '../../../containers/Form';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import Form from '../Form';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';
import type {Resource} from '../../../types';

export default class DeleteToolbarAction extends AbstractFormToolbarAction {
    @observable showDialog: boolean = false;
    @observable showDeleteReferencedResourcesDialog: boolean = false;
    @observable referencingResourcesData: {
        referencingResources: Resource[],
        referencingResourcesCount: number,
        resource: Resource,
    } | null = null;
    @observable showDeleteDependantsDialog: boolean = false;
    @observable dependantResourcesData: {
        dependantResources: Resource[][],
        dependantResourcesCount: number,
    } | null = null;

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
        this.showDeleteReferencedResourcesDialog = false;
        this.referencingResourcesData = null;
    };

    renderDeleteReferencedResourceDialog() {
        if (!this.showDeleteReferencedResourcesDialog || this.referencingResourcesData === null) {
            return null;
        }

        const {resource, referencingResources, referencingResourcesCount} = this.referencingResourcesData;

        return (
            <DeleteReferencedResourceDialog
                allowDeletion={this.allowConflictDeletion}
                loading={this.resourceFormStore.deleting}
                onCancel={this.handleDeleteReferencedResourcesDialogCancel}
                onConfirm={this.handleDeleteReferencedResourcesDialogConfirm}
                referencingResources={referencingResources}
                referencingResourcesCount={referencingResourcesCount}
                resource={resource}
            />
        );
    }

    handleDeleteDependantsDialogFinish = () => {
        this.delete();
    };

    handleDeleteDependantsDialogCancel = () => {
        this.closeDeleteDependantsDialog();
    };

    handleDeleteDependantsDialogClose = () => {
        this.closeDeleteDependantsDialog();
    };

    @action closeDeleteDependantsDialog = () => {
        this.showDeleteDependantsDialog = false;
        this.dependantResourcesData = null;
    };

    @computed get deleteDependantsDialogRequestOptions() {
        const {locale, options: resourceFormStoreOptions = {}} = this.resourceFormStore;

        const options = resourceFormStoreOptions;

        if (locale) {
            options.locale = locale.get();
        }

        return options;
    }

    renderDeleteDependantsDialog() {
        if (!this.showDeleteDependantsDialog || this.dependantResourcesData === null) {
            return null;
        }

        const {dependantResourcesCount, dependantResources} = this.dependantResourcesData;

        return (
            <DeleteDependantsDialog
                dependantResources={dependantResources}
                dependantResourcesCount={dependantResourcesCount}
                onCancel={this.handleDeleteDependantsDialogCancel}
                onClose={this.handleDeleteDependantsDialogClose}
                onFinish={this.handleDeleteDependantsDialogFinish}
                requestOptions={this.deleteDependantsDialogRequestOptions}
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
                {this.renderDeleteDependantsDialog()}
            </Fragment>
        );
    }

    getToolbarItemConfig() {
        const {
            visible_condition: visibleCondition,
            delete_locale: deleteLocale = false,
        } = this.options;

        const {id, data} = this.resourceFormStore;

        const visibleConditionFulfilled = !visibleCondition || jexl.evalSync(visibleCondition, data);
        const disableCondition = !id || (deleteLocale && jexl.evalSync('contentLocales.length == 1', data));

        if (visibleConditionFulfilled) {
            return {
                disabled: !!disableCondition,
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
                this.closeDeleteDependantsDialog();
                this.closeDeleteReferencedResourceDialog();

                this.navigateBack();
            })
            .catch(action((response) => {
                response.json().then(action((data) => {
                    this.closeDialog();
                    this.closeDeleteDependantsDialog();
                    this.closeDeleteReferencedResourceDialog();

                    if (response.status === 409 && data.code === 1105) {
                        this.showDeleteDependantsDialog = true;
                        this.dependantResourcesData = {
                            dependantResources: data.dependantResources,
                            dependantResourcesCount: data.dependantResourcesCount,
                        };

                        return;
                    }

                    if (response.status === 409 && data.code === 1106) {
                        this.showDeleteReferencedResourcesDialog = true;
                        this.referencingResourcesData = {
                            resource: data.resource,
                            referencingResources: data.referencingResources,
                            referencingResourcesCount: data.referencingResourcesCount,
                        };

                        return;
                    }

                    const error = data.detail || data.message;

                    if (error) {
                        this.form.errors.push(data.detail || data.message);
                    }
                }));
            }));
    };
}
