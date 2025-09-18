// @flow
import React from 'react';
import {observable} from 'mobx';
import {Dialog, Input, Form} from 'sulu-admin-bundle/components';
import {userStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import SingleSelect from 'sulu-admin-bundle/components/SingleSelect';
import SingleMediaSelection from '../../SingleMediaSelection';
import type {Value} from '../../SingleMediaSelection';
import type {Media} from '../../../types';
import type {LinkTypeOverlayProps} from 'sulu-admin-bundle/containers/Link/types';

export default class MediaLinkTypeOverlay extends React.Component<LinkTypeOverlayProps> {
    handleChange = (value: Value, media: ?Media) => {
        const {onHrefChange} = this.props;

        onHrefChange(value.id, media);
    };

    render() {
        const {
            href,
            locale,
            onCancel,
            onConfirm,
            onTitleChange,
            onTargetChange,
            onAnchorChange,
            open,
            title,
            target,
            anchor,
            options,
        } = this.props;

        const targets = options?.targets || ['_blank', '_self', '_parent', '_top'];

        if (typeof href === 'string') {
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
                            value={{displayOption: undefined, id: href}}
                        />
                    </Form.Field>

                    {!!onAnchorChange &&
                        <Form.Field label={translate('sulu_admin.link_anchor')}>
                            <Input onChange={onAnchorChange} value={anchor} />
                        </Form.Field>
                    }

                    {!!onTargetChange &&
                        <Form.Field label={translate('sulu_admin.link_target')} required={true}>
                            <SingleSelect onChange={onTargetChange} value={target}>
                                {targets.map((targetValue) => (
                                    <SingleSelect.Option key={targetValue} value={targetValue}>
                                        {translate(`sulu_admin.link_target${targetValue}`)}
                                    </SingleSelect.Option>
                                ))}
                            </SingleSelect>
                        </Form.Field>
                    }

                    {!!onTitleChange &&
                        <Form.Field label={translate('sulu_admin.link_title')}>
                            <Input onChange={onTitleChange} value={title} />
                        </Form.Field>
                    }
                </Form>
            </Dialog>
        );
    }
}
