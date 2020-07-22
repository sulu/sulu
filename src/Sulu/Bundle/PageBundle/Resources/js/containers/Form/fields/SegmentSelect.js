// @flow
import React from 'react';
import {computed} from 'mobx';
import {SingleSelect} from 'sulu-admin-bundle/components';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {translate} from 'sulu-admin-bundle/utils';
import webspaceStore from '../../../stores/webspaceStore';

export default class SegmentSelect extends React.Component<FieldTypeProps<string>> {
    @computed get webspace() {
        const {formInspector} = this.props;

        return webspaceStore.getWebspace(formInspector.metadataOptions.webspace);
    }

    handleChange = (value: string) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, value} = this.props;

        return (
            <SingleSelect
                disabled={!!disabled}
                onChange={this.handleChange}
                value={value}
            >
                <SingleSelect.Option>
                    {translate('sulu_admin.none_selected')}
                </SingleSelect.Option>
                {this.webspace.segments.map(({key, title}) => (
                    <SingleSelect.Option key={key} value={key}>
                        {title}
                    </SingleSelect.Option>
                ))}
            </SingleSelect>
        );
    }
}
