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

export default class SetUnpublishedToolbarAction extends AbstractFormToolbarAction {
    @observable showUnpublishDialog = false;
    @observable unpublishing = false;

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
            visible_condition: visibleCondition,
        } = this.options;

        const {id, data} = this.resourceFormStore;

        const {published} = data;

        const visibleConditionFulfilled = !visibleCondition || jexl.evalSync(visibleCondition, data);

        if (visibleConditionFulfilled) {
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
            resourceKey,
        } = this.resourceFormStore;

        if (!id) {
            throw new Error(
                'The page can only be unpublished if an ID is given! This should not happen and is likely a bug.'
            );
        }

        this.unpublishing = true;

        ResourceRequester.post(
            resourceKey,
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
            this.form.showSuccessSnackbar();
            this.resourceFormStore.setMultiple(response);
            this.resourceFormStore.dirty = false;
        }));
    };

    @action handleUnpublishDialogClose = () => {
        this.showUnpublishDialog = false;
    };
}
