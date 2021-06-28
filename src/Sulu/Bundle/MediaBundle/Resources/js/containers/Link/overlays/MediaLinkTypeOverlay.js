// @flow
import React from 'react';
import {observable} from 'mobx';
import {Dialog, Input, Form} from 'sulu-admin-bundle/components';
import {userStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import SingleMediaSelection from '../../SingleMediaSelection';
import type {Value} from '../../SingleMediaSelection';
import type {Media} from '../../../types';
import type {LinkTypeOverlayProps} from 'sulu-admin-bundle/containers/Link/types';

export default class MediaLinkTypeOverlay extends React.Component<LinkTypeOverlayProps> {
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
                            value={{displayOption: undefined, id}}
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
