// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {Dialog} from 'sulu-admin-bundle/components';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import {AbstractFormToolbarAction} from 'sulu-admin-bundle/views';
import CopyLocaleDialog from './CopyLocaleDialog';

export default class EditToolbarAction extends AbstractFormToolbarAction {
    @observable showCopyLocaleDialog = false;
    @observable showDeleteDraftDialog = false;
    @observable deletingDraft = false;

    getNode() {
        const {
            resourceFormStore: {
                id,
                data: {
                    availableLocales,
                },
                locale,
                options: {
                    webspace,
                },
            },
            locales,
        } = this;

        if (!id) {
            return null;
        }

        if (!locales || !locale) {
            throw new Error('The EditToolbarAction for pages only works with locales!');
        }

        if (!webspace) {
            throw new Error('The EditToolbarAction for pages only works with a webspace!');
        }

        return (
            <Fragment key="sulu_page.edit">
                <CopyLocaleDialog
                    availableLocales={availableLocales}
                    id={id}
                    locale={locale.get()}
                    locales={locales}
                    onClose={this.handleCopyLocaleDialogClose}
                    open={this.showCopyLocaleDialog}
                    webspace={webspace}
                />
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.deletingDraft}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleDeleteDraftDialogClose}
                    onConfirm={this.handleDeleteDraftDialogConfirm}
                    open={this.showDeleteDraftDialog}
                    title={translate('sulu_page.delete_draft_warning_title')}
                >
                    {translate('sulu_page.delete_draft_warning_text')}
                </Dialog>
            </Fragment>
        );
    }

    getToolbarItemConfig() {
        const {id, data} = this.resourceFormStore;
        const {published, publishedState} = data;

        return {
            type: 'dropdown',
            label: translate('sulu_admin.edit'),
            icon: 'su-pen',
            options: [
                {
                    disabled: !id,
                    label: translate('sulu_admin.copy_locale'),
                    onClick: action(() => {
                        this.showCopyLocaleDialog = true;
                    }),
                },
                {
                    disabled: !id || !published || publishedState,
                    label: translate('sulu_page.delete_draft'),
                    onClick: action(() => {
                        this.showDeleteDraftDialog = true;
                    }),
                },
            ],
        };
    }

    @action handleCopyLocaleDialogClose = (copied: boolean) => {
        if (copied) {
            this.form.showSuccessSnackbar();
        }

        this.showCopyLocaleDialog = false;
    };

    @action handleDeleteDraftDialogConfirm = () => {
        const {
            id,
            locale,
            options: {
                webspace,
            },
        } = this.resourceFormStore;

        if (!id) {
            throw new Error(
                'The draft can only be deleted if an ID is given! This should not happen and is likely a bug.'
            );
        }

        this.deletingDraft = true;

        ResourceRequester.post(
            'pages',
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
