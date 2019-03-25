// @flow
import React from 'react';
import {observable} from 'mobx';
import {Dialog, Form, SingleSelect} from 'sulu-admin-bundle/components';
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
        const {id, locale, onCancel, onConfirm, onTargetChange, open, target} = this.props;

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
                    <Form.Field label={translate('sulu_admin.link_target')} required={true}>
                        <SingleSelect onChange={onTargetChange} value={target}>
                            <SingleSelect.Option value="_blank">_blank</SingleSelect.Option>
                            <SingleSelect.Option value="_self">_self</SingleSelect.Option>
                            <SingleSelect.Option value="_parent">_parent</SingleSelect.Option>
                            <SingleSelect.Option value="_top">_top</SingleSelect.Option>
                        </SingleSelect>
                    </Form.Field>

                    <Form.Field label={translate('sulu_admin.link_url')} required={true}>
                        <SingleMediaSelection
                            locale={locale || observable.box(userStore.contentLocale)}
                            onChange={this.handleChange}
                            value={{id}}
                        />
                    </Form.Field>
                </Form>
            </Dialog>
        );
    }
}
