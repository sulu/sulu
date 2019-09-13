// @flow
import React from 'react';
import {action, observable} from 'mobx';
import jexl from 'jexl';
import Dialog from '../../../components/Dialog';
import ResourceRequester from '../../../services/ResourceRequester';
import {translate} from '../../../utils/Translator';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class DeleteDraftToolbarAction extends AbstractFormToolbarAction {
    @observable showDeleteDraftDialog = false;
    @observable deletingDraft = false;

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
            display_condition: displayCondition,
        } = this.options;

        const {id, data} = this.resourceFormStore;

        const {published, publishedState} = data;

        const publishAllowed = !displayCondition
            || jexl.evalSync(displayCondition, this.resourceFormStore.data);

        if (publishAllowed) {
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
            this.resourceFormStore.setMultiple(response);
            this.resourceFormStore.dirty = false;
        }));
    };

    @action handleDeleteDraftDialogClose = () => {
        this.showDeleteDraftDialog = false;
    };
}
