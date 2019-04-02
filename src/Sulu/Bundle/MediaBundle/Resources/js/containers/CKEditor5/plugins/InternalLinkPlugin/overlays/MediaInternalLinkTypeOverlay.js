// @flow
import React from 'react';
import {observable} from 'mobx';
import {Dialog, Input, Form} from 'sulu-admin-bundle/components';
import {userStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import type {InternalLinkTypeOverlayProps} from 'sulu-admin-bundle/types';
import SingleMediaSelection from '../../../../SingleMediaSelection';
import type {Value} from '../../../../SingleMediaSelection/types';
import type {Media} from '../../../../../types';

export default class MediaInternalLinkTypeOverlay extends React.Component<InternalLinkTypeOverlayProps> {
    handleChange = (value: Value, media: ?Media) => {
        const {onResourceChange} = this.props;

        onResourceChange(value.id, media);
    };

    render() {
        const {id, locale, onCancel, onConfirm, onTitleChange, open, title} = this.props;

        if (typeof id === 'string') {
            throw new Error('The id of a media should always be a number!');
        }

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmText={translate('sulu_admin.confirm')}
                onCancel={onCancel}
                onConfirm={onConfirm}
                open={open}
                title={translate('sulu_admin.link')}
            >
                <Form>
                    <Form.Field label={translate('sulu_admin.link_url')} required={true}>
                        <SingleMediaSelection
                            locale={locale || observable.box(userStore.contentLocale)}
                            onChange={this.handleChange}
                            value={{id}}
                        />
                    </Form.Field>

                    <Form.Field label={translate('sulu_admin.link_title')}>
                        <Input onChange={onTitleChange} value={title} />
                    </Form.Field>
                </Form>
            </Dialog>
        );
    }
}
