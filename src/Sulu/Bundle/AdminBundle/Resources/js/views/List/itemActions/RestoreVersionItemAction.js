// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {AbstractListItemAction} from '../../../views';
import {Dialog} from '../../../components';
import {translate} from '../../../utils/Translator';
import {ResourceRequester} from '../../../services';
import type {Node} from 'react';

export default class RestoreVersionItemAction extends AbstractListItemAction {
    @observable versionToBeRestored: ?number = undefined;
    @observable restoring: boolean = false;

    @action handleRestoreClick = (version: number) => {
        this.versionToBeRestored = version;
    };

    @action handleDialogCancel = () => {
        this.versionToBeRestored = undefined;
    };

    @action handleDialogConfirm = () => {
        const {success_view: successView} = this.options;
        const {id, locale, webspace} = this.router.attributes;

        if (typeof successView !== 'string') {
            throw new Error('The "success_view" option cannot be null and must contain a string value!');
        }

        this.restoring = true;
        ResourceRequester
            .post(this.listStore.resourceKey, {}, {
                action: 'restore',
                version: this.versionToBeRestored,
                id,
                locale,
                webspace,
            })
            .then(action(() => {
                this.restoring = false;
                this.versionToBeRestored = undefined;

                this.router.navigate(successView, {id, locale, webspace});
            }));
    };

    getItemActionConfig(item: ?Object) {
        return {
            icon: 'su-process',
            onClick: item?.version ? () => this.handleRestoreClick(item.version) : undefined,
            disabled: !item?.version,
        };
    }

    getNode(): Node {
        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.restoring}
                confirmText={translate('sulu_admin.ok')}
                key="restore_version"
                onCancel={this.handleDialogCancel}
                onConfirm={this.handleDialogConfirm}
                open={!!this.versionToBeRestored}
                title={translate('sulu_page.restore_version')}
            >
                {translate('sulu_page.restore_version_text')}
            </Dialog>
        );
    }
}
