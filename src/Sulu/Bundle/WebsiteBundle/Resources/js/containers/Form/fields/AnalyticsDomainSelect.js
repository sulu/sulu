// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import {MultiSelect} from 'sulu-admin-bundle/components';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {webspaceStore} from 'sulu-page-bundle/stores';

@observer
class AnalyticsDomainSelect extends React.Component<FieldTypeProps<Array<string>>> {
    @computed get webspace() {
        const {formInspector} = this.props;

        return webspaceStore.getWebspace(formInspector.options.webspace);
    }

    handleChange = (value: Array<string>) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, value} = this.props;

        return (
            <MultiSelect
                disabled={!!disabled}
                onChange={this.handleChange}
                values={value || []}
            >
                {this.webspace.urls.map(({url}) => (
                    <MultiSelect.Option key={url} value={url}>
                        {url}
                    </MultiSelect.Option>
                ))}
            </MultiSelect>
        );
    }
}

export default AnalyticsDomainSelect;
