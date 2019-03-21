// @flow
import React from 'react';
import SingleSelection from '../../../../SingleSelection';
import {translate} from '../../../../../utils/Translator';
import Dialog from '../../../../../components/Dialog';
import Form from '../../../../../components/Form';
import SingleSelect from '../../../../../components/SingleSelect';
import type {InternalLinkTypeOverlayProps} from '../types';

export default class InternalLinkTypeOverlay extends React.Component<InternalLinkTypeOverlayProps> {
    render() {
        const {id, locale, onCancel, onConfirm, onTargetChange, onIdChange, open, options, target} = this.props;

        if (!options) {
            throw new Error('The InternalLinkTypeOverlay needs some options in order to work!');
        }

        const {displayProperties, emptyText, icon, listAdapter, overlayTitle, resourceKey} = options;

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
                        <SingleSelection
                            adapter={listAdapter}
                            displayProperties={displayProperties}
                            emptyText={emptyText}
                            icon={icon}
                            listKey={resourceKey}
                            locale={locale}
                            onChange={onIdChange}
                            overlayTitle={overlayTitle}
                            resourceKey={resourceKey}
                            value={id}
                        />
                    </Form.Field>
                </Form>
            </Dialog>
        );
    }
}
