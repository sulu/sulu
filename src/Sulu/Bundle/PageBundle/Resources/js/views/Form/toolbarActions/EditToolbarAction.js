// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import jexl from 'jexl';
import {Dialog} from 'sulu-admin-bundle/components';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import {AbstractFormToolbarAction} from 'sulu-admin-bundle/views';
import CopyLocaleDialog from './CopyLocaleDialog';

// TODO remove
export default class EditToolbarAction extends AbstractFormToolbarAction {
    @observable showCopyLocaleDialog = false;
    @observable showDeleteDraftDialog = false;
    @observable deletingDraft = false;
    @observable showUnpublishDialog = false;
    @observable unpublishing = false;

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
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.unpublishing}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleUnpublishDialogClose}
                    onConfirm={this.handleUnpublishDialogConfirm}
                    open={this.showUnpublishDialog}
                    title={translate('sulu_page.unpublish_warning_title')}
                >
                    {translate('sulu_page.unpublish_warning_text')}
                </Dialog>
            </Fragment>
        );
    }

    getToolbarItemConfig() {
        const {
            publish_display_condition: publishDisplayCondition,
            copy_locale_display_condition: copyLocaleDisplayCondition,
        } = this.options;

        const {id, data} = this.resourceFormStore;

        const {published, publishedState} = data;

        const publishAllowed = !publishDisplayCondition
            || jexl.evalSync(publishDisplayCondition, this.resourceFormStore.data);

        const copyLocaleAllowed = !copyLocaleDisplayCondition
            || jexl.evalSync(copyLocaleDisplayCondition, this.resourceFormStore.data);

        const options = [];

        if (copyLocaleAllowed) {
            options.push({
                disabled: !id,
                label: translate('sulu_admin.copy_locale'),
                onClick: action(() => {
                    this.showCopyLocaleDialog = true;
                }),
            });
        }

        if (publishAllowed) {
            options.push({
                disabled: !id || !published || publishedState,
                label: translate('sulu_page.delete_draft'),
                onClick: action(() => {
                    this.showDeleteDraftDialog = true;
                }),
            });
            options.push({
                disabled: !id || !published,
                label: translate('sulu_page.unpublish'),
                onClick: action(() => {
                    this.showUnpublishDialog = true;
                }),
            });
        }

        if (options.length === 0) {
            return;
        }

        return {
            type: 'dropdown',
            label: translate('sulu_admin.edit'),
            icon: 'su-pen',
            options,
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

    @action handleUnpublishDialogConfirm = () => {
        const {
            id,
            locale,
            options: {
                webspace,
            },
        } = this.resourceFormStore;

        if (!id) {
            throw new Error(
                'The page can only be unpublished if an ID is given! This should not happen and is likely a bug.'
            );
        }

        this.unpublishing = true;

        ResourceRequester.post(
            'pages',
            undefined,
            {
                action: 'unpublish',
                locale,
                id,
                webspace,
            }
        ).then(action((response) => {
            this.unpublishing = false;
            this.showUnpublishDialog = false;
            this.resourceFormStore.setMultiple(response);
            this.resourceFormStore.dirty = false;
        }));
    };

    @action handleUnpublishDialogClose = () => {
        this.showUnpublishDialog = false;
    };
}
