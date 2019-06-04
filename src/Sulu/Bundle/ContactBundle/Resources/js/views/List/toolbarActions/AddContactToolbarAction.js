// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {Form, Overlay} from 'sulu-admin-bundle/components';
import {ResourceSingleSelect, SingleAutoComplete} from 'sulu-admin-bundle/containers';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import {AbstractListToolbarAction} from 'sulu-admin-bundle/views';
import addContactToolbarActionStyles from './addContactToolbarAction.scss';

class AddMediaToolbarAction extends AbstractListToolbarAction {
    @observable showOverlay: boolean = false;
    @observable saving: boolean = false;
    @observable position: ?number = undefined;
    @observable contact: ?Object = undefined;

    getNode() {
        return (
            <Overlay
                confirmDisabled={!this.contact}
                confirmLoading={this.saving}
                confirmText={translate('sulu_admin.add')}
                key="sulu_contact.add_media"
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={this.showOverlay}
                size="small"
                title={translate('sulu_contact.add_contact_to_organization')}
            >
                <div className={addContactToolbarActionStyles.overlay}>
                    <Form>
                        <Form.Field label={translate('sulu_contact.people')}>
                            <SingleAutoComplete
                                displayProperty="fullName"
                                onChange={this.handleContactChange}
                                options={{excludedAccountId: this.listStore.options.accountId}}
                                resourceKey="contacts"
                                searchProperties={['fullName']}
                                value={this.contact}
                            />
                        </Form.Field>
                        <Form.Field label={translate('sulu_contact.position')}>
                            <ResourceSingleSelect
                                displayProperty="position"
                                idProperty="id"
                                onChange={this.handlePositionChange}
                                resourceKey="contact_positions"
                                value={this.position}
                            />
                        </Form.Field>
                    </Form>
                </div>
            </Overlay>
        );
    }

    getToolbarItemConfig() {
        return {
            icon: 'su-plus-circle',
            label: translate('sulu_admin.add'),
            onClick: action(() => {
                this.showOverlay = true;
            }),
            type: 'button',
        };
    }

    @action handleContactChange = (contact: ?Object) => {
        this.contact = contact;
    };

    @action handlePositionChange = (position: ?number) => {
        this.position = position;
    };

    @action handleConfirm = () => {
        if (!this.contact) {
            throw new Error('The contact must be selected in order to confirm the dialog!');
        }

        this.saving = true;
        ResourceRequester.put(
            'account_contacts',
            {
                position: this.position,
            },
            {accountId: this.listStore.options.accountId, id: this.contact.id}
        ).then(action(() => {
            this.saving = false;
            this.showOverlay = false;
            this.resetFields();
            this.listStore.reload();
        }));
    };

    @action handleClose = () => {
        this.showOverlay = false;
        this.resetFields();
    };

    @action resetFields = () => {
        this.contact = undefined;
        this.position = undefined;
    };
}

export default AddMediaToolbarAction;
