// @flow
import React from 'react';
import {action, observable} from 'mobx';
import jexl from 'jexl';
import Dialog from '../../../components/Dialog';
import ResourceRequester from '../../../services/ResourceRequester';
import {translate} from '../../../utils/Translator';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class SetUnpublishedToolbarAction extends AbstractFormToolbarAction {
    @observable showUnpublishDialog = false;
    @observable unpublishing = false;

    getNode() {
        const {
            resourceFormStore: {
                id,
                locale,
            },
        } = this;

        if (!id) {
            return null;
        }

        if (!locale) {
            throw new Error('The SetUnpublishedToolbarAction only works with locale!');
        }

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.unpublishing}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_admin.set_unpublished"
                onCancel={this.handleUnpublishDialogClose}
                onConfirm={this.handleUnpublishDialogConfirm}
                open={this.showUnpublishDialog}
                title={translate('sulu_page.unpublish_warning_title')}
            >
                {translate('sulu_page.unpublish_warning_text')}
            </Dialog>
        );
    }

    getToolbarItemConfig() {
        const {
            display_condition: displayCondition,
        } = this.options;

        const {id, data} = this.resourceFormStore;

        const {published} = data;

        const publishAllowed = !displayCondition
            || jexl.evalSync(displayCondition, this.resourceFormStore.data);

        if (publishAllowed) {
            return {
                disabled: !id || !published,
                label: translate('sulu_page.unpublish'),
                onClick: action(() => {
                    this.showUnpublishDialog = true;
                }),
                type: 'button',
            };
        }
    }

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
