// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {translate} from 'sulu-admin-bundle/utils';
import {AbstractToolbarAction} from 'sulu-admin-bundle/views';
import CopyLocaleDialog from './CopyLocaleDialog';

export default class EditToolbarAction extends AbstractToolbarAction {
    @observable showCopyLocaleDialog = false;

    getNode() {
        const {
            formStore: {
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
            <CopyLocaleDialog
                availableLocales={availableLocales}
                id={id}
                key="sulu_admin.edit"
                locale={locale.get()}
                locales={locales}
                onClose={this.handleCopyLocaleDialogClose}
                open={this.showCopyLocaleDialog}
                webspace={webspace}
            />
        );
    }

    getToolbarItemConfig() {
        const {id} = this.formStore;

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
            ],
        };
    }

    @action handleCopyLocaleDialogClose = (copied: boolean) => {
        if (copied) {
            this.form.showSuccessSnackbar();
        }

        this.showCopyLocaleDialog = false;
    };
}
