// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {MultiSelect} from 'sulu-admin-bundle/components';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {webspaceStore} from 'sulu-page-bundle/stores';

@observer
class AnalyticsDomainSelect extends React.Component<FieldTypeProps<Array<string>>> {
    handleChange = (value: Array<string>) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, formInspector, value} = this.props;

        return (
            <MultiSelect
                disabled={!!disabled}
                onChange={this.handleChange}
                values={value || []}
            >
                {webspaceStore.getWebspace(formInspector.options.webspace).urls.map(({url}) => (
                    <MultiSelect.Option key={url} value={url}>
                        {url}
                    </MultiSelect.Option>
                ))}
            </MultiSelect>
        );
    }
}

export default AnalyticsDomainSelect;
