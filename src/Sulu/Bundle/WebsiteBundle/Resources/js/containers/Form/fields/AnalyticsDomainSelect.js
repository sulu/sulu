// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {MultiSelect} from 'sulu-admin-bundle/components';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {webspaceStore} from 'sulu-page-bundle/stores';
import type {Webspace} from 'sulu-page-bundle/types';

@observer
export default class AnalyticsDomainSelect extends React.Component<FieldTypeProps<Array<string>>> {
    @observable webspace: Webspace;

    componentDidMount() {
        const {formInspector} = this.props;
        webspaceStore.loadWebspace(formInspector.options.webspace).then(action((webspace) => {
            this.webspace = webspace;
        }));
    }

    handleChange = (value: Array<string>) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, value} = this.props;

        if (!this.webspace) {
            return null;
        }

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
