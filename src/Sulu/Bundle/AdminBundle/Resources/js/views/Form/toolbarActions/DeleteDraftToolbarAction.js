// @flow
import React from 'react';
import {action, observable} from 'mobx';
import jexl from 'jexl';
import log from 'loglevel';
import Dialog from '../../../components/Dialog';
import ResourceRequester from '../../../services/ResourceRequester';
import {translate} from '../../../utils/Translator';
import {ResourceFormStore} from '../../../containers/Form';
import Form from '../Form';
import Router from '../../../services/Router';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class DeleteDraftToolbarAction extends AbstractFormToolbarAction {
    @observable showDeleteDraftDialog = false;
    @observable deletingDraft = false;

    constructor(
        resourceFormStore: ResourceFormStore,
        form: Form,
        router: Router,
        locales: ?Array<string>,
        options: {[key: string]: mixed}
    ) {
        const {
            display_condition: displayCondition,
            visible_condition: visibleCondition,
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

        super(resourceFormStore, form, router, locales, options);
    }

    getNode() {
        const {
            resourceFormStore: {
                id,
            },
        } = this;

        if (!id) {
            return null;
        }

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.deletingDraft}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_admin.delete_draft"
                onCancel={this.handleDeleteDraftDialogClose}
                onConfirm={this.handleDeleteDraftDialogConfirm}
                open={this.showDeleteDraftDialog}
                title={translate('sulu_page.delete_draft_warning_title')}
            >
                {translate('sulu_page.delete_draft_warning_text')}
            </Dialog>
        );
    }

    getToolbarItemConfig() {
        const {
            visible_condition: visibleCondition,
        } = this.options;

        const {id, data} = this.resourceFormStore;

        const {published, publishedState} = data;

        const visibleConditionFulfilled = !visibleCondition || jexl.evalSync(visibleCondition, data);

        if (visibleConditionFulfilled) {
            return {
                disabled: !id || !published || publishedState,
                label: translate('sulu_page.delete_draft'),
                onClick: action(() => {
                    this.showDeleteDraftDialog = true;
                }),
                type: 'button',
            };
        }
    }

    @action handleDeleteDraftDialogConfirm = () => {
        const {
            id,
            locale,
            options: {
                webspace,
            },
            resourceKey,
        } = this.resourceFormStore;

        if (!id) {
            throw new Error(
                'The draft can only be deleted if an ID is given! This should not happen and is likely a bug.'
            );
        }

        this.deletingDraft = true;

        ResourceRequester.post(
            resourceKey,
            undefined,
            {
                action: 'remove-draft',
                locale,
                id,
                webspace,
            }
        ).then(action((response) => {
            this.deletingDraft = false;
            this.showDeleteDraftDialog = false;
            this.form.showSuccessSnackbar();
            this.resourceFormStore.setMultiple(response);
            this.resourceFormStore.dirty = false;
        }));
    };

    @action handleDeleteDraftDialogClose = () => {
        this.showDeleteDraftDialog = false;
    };
}
