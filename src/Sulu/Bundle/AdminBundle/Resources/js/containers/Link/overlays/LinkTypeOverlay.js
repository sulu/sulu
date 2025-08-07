// @flow
import React from 'react';
import SingleSelection from '../../SingleSelection';
import {translate} from '../../../utils';
import Dialog from '../../../components/Dialog';
import Form from '../../../components/Form';
import Input from '../../../components/Input';
import SingleSelect from '../../../components/SingleSelect';
import type {LinkTypeOverlayProps} from '../types';

export default class LinkTypeOverlay extends React.Component<LinkTypeOverlayProps> {
    render() {
        const {
            query,
            anchor,
            href,
            locale,
            onQueryChange,
            onAnchorChange,
            onCancel,
            onConfirm,
            onTargetChange,
            onTitleChange,
            onHrefChange,
            open,
            options,
            target,
            title,
        } = this.props;

        if (!options || !options.targets) {
            throw new Error('The LinkTypeOverlay needs some options in order to work!');
        }

        const {
            displayProperties,
            emptyText = '',
            icon = '',
            listAdapter = '',
            overlayTitle = '',
            resourceKey,
            targets = ['_blank', '_self', '_parent', '_top'],
        } = options;

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
                        <SingleSelection
                            adapter={listAdapter}
                            displayProperties={displayProperties}
                            emptyText={emptyText}
                            icon={icon}
                            listKey={resourceKey}
                            locale={locale}
                            onChange={onHrefChange}
                            overlayTitle={overlayTitle}
                            resourceKey={resourceKey}
                            value={href}
                        />
                    </Form.Field>

                    {onQueryChange &&
                        <Form.Field label={translate('sulu_admin.link_query')}>
                            <Input onChange={onQueryChange} value={query} />
                        </Form.Field>
                    }

                    {onAnchorChange &&
                        <Form.Field label={translate('sulu_admin.link_anchor')}>
                            <Input onChange={onAnchorChange} value={anchor} />
                        </Form.Field>
                    }

                    {onTargetChange &&
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

                    {onTitleChange &&
                        <Form.Field label={translate('sulu_admin.link_title')}>
                            <Input onChange={onTitleChange} value={title} />
                        </Form.Field>
                    }
                </Form>
            </Dialog>
        );
    }
}
