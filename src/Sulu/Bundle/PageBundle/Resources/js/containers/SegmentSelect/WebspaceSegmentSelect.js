// @flow
import React from 'react';
import {SingleSelect} from 'sulu-admin-bundle/components';
import fieldStyles from 'sulu-admin-bundle/components/Form/field.scss';
import {translate} from 'sulu-admin-bundle/utils';
import type {Webspace} from '../../stores/webspaceStore/types';
import webspaceSegmentSelectStyles from './webspaceSegmentSelect.scss';

export default class WebspaceSegmentSelect extends React.Component<{
    disabled: boolean,
    onChange: (webspaceKey: string, segment: ?string) => void,
    value: ?string,
    webspace: Webspace,
}> {
    handleSelectChange = (value: ?string) => {
        const {onChange, webspace} = this.props;

        onChange(webspace.key, value);
    };

    render() {
        const {disabled, value, webspace} = this.props;

        return (
            <div className={webspaceSegmentSelectStyles.webspaceSection}>
                <label className={fieldStyles.label}>
                    {webspace.name} - {translate('sulu_admin.segments')}
                </label>

                <SingleSelect
                    disabled={!!disabled}
                    onChange={this.handleSelectChange}
                    value={value}
                >
                    <SingleSelect.Option>
                        {translate('sulu_admin.none_selected')}
                    </SingleSelect.Option>
                    {webspace.segments.map(({key, title}) => (
                        <SingleSelect.Option key={key} value={key}>
                            {title}
                        </SingleSelect.Option>
                    ))}
                </SingleSelect>
            </div>
        );
    }
}
