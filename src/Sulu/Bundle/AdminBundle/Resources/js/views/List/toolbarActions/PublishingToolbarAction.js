// @flow
import React from 'react';
import {action, observable} from 'mobx';
import Dialog from '../../../components/Dialog';
import ResourceRequester from '../../../services/ResourceRequester';
import {translate} from '../../../utils/Translator';
import AbstractListToolbarAction from './AbstractListToolbarAction';

/**
 * Publishes or unpublishes the selected items in the list's locale, one request per item.
 */
export default class PublishingToolbarAction extends AbstractListToolbarAction {
    @observable loading: boolean = false;
    @observable showUnpublishDialog: boolean = false;

    // a ghost item has no content in this locale, and a live item without a draft would only get an identical version
    get publishableItems(): Array<Object> {
        return this.listStore.selections.filter((item) => !item.ghostLocale && !item.publishedState);
    }

    get unpublishableItems(): Array<Object> {
        return this.listStore.selections.filter((item) => !item.ghostLocale && !!item.published);
    }

    getNode() {
        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.loading}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_admin.publishing"
                onCancel={this.handleUnpublishCancel}
                onConfirm={this.handleUnpublishConfirm}
                open={this.showUnpublishDialog}
                title={translate('sulu_admin.unpublish_selection_warning_title')}
            >
                {translate('sulu_admin.unpublish_selection_warning_text')}
            </Dialog>
        );
    }

    getToolbarItemConfig() {
        return {
            disabled: this.publishableItems.length === 0 && this.unpublishableItems.length === 0,
            icon: 'su-publish',
            label: translate('sulu_admin.publishing'),
            loading: this.loading,
            options: [
                {
                    disabled: this.publishableItems.length === 0,
                    label: translate('sulu_admin.publish'),
                    onClick: this.handlePublishClick,
                },
                {
                    disabled: this.unpublishableItems.length === 0,
                    label: translate('sulu_admin.unpublish'),
                    onClick: this.handleUnpublishClick,
                },
            ],
            type: 'dropdown',
        };
    }

    handlePublishClick = () => {
        this.applyTransition('publish', this.publishableItems);
    };

    @action handleUnpublishClick = () => {
        this.showUnpublishDialog = true;
    };

    handleUnpublishConfirm = () => {
        this.applyTransition('unpublish', this.unpublishableItems);
    };

    @action handleUnpublishCancel = () => {
        this.showUnpublishDialog = false;
    };

    @action applyTransition(transition: string, items: Array<Object>) {
        const {listStore} = this;
        this.loading = true;

        return Promise.all(items.map((item) => ResourceRequester.post(
            listStore.resourceKey,
            undefined,
            {...listStore.queryOptions, action: transition, id: item.id}
        ).then(() => undefined, getErrorMessage))).then(action((errors) => {
            const failedIds = items.filter((item, index) => errors[index]).map((item) => item.id);

            errors.filter(Boolean).forEach((error) => this.list.errors.push(error));

            // only failed items stay selected, so the selection shows what to retry
            listStore.selectionIds
                .filter((id) => !failedIds.includes(id))
                .forEach((id) => listStore.deselectById(id));

            this.loading = false;
            this.showUnpublishDialog = false;
            listStore.reload();
        }));
    }
}

// a failed request rejects with the fetch response
function getErrorMessage(response: Object): Promise<string> {
    const fallback = translate('sulu_admin.unexpected_error');

    if (typeof response?.json !== 'function') {
        return Promise.resolve(fallback);
    }

    return response.json()
        .then((data) => data?.detail || data?.title || fallback)
        .catch(() => fallback);
}
