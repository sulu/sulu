// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {SingleSelect} from 'sulu-admin-bundle/components';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {webspaceStore} from 'sulu-page-bundle/stores';
import type {Webspace} from 'sulu-page-bundle/types';

export default @observer class CustomUrlsLocaleSelect extends React.Component<FieldTypeProps<string>> {
    @observable webspace: Webspace;

    componentDidMount() {
        const {formInspector} = this.props;
        webspaceStore.loadWebspace(formInspector.options.webspace).then(action((webspace) => {
            this.webspace = webspace;
        }));
    }

    handleChange = (value: string) => {
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
            <SingleSelect
                disabled={!!disabled}
                onChange={this.handleChange}
                value={value}
            >
                {this.webspace.allLocalizations.map(({localization}) => (
                    <SingleSelect.Option key={localization} value={localization}>
                        {localization}
                    </SingleSelect.Option>
                ))}
            </SingleSelect>
        );
    }
}
