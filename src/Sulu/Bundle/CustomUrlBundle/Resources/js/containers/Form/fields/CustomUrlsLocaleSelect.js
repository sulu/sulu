// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import {SingleSelect} from 'sulu-admin-bundle/components';
import {webspaceStore} from 'sulu-page-bundle/stores';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';

@observer
class CustomUrlsLocaleSelect extends React.Component<FieldTypeProps<string>> {
    @computed get webspace() {
        const {formInspector} = this.props;

        return webspaceStore.getWebspace(formInspector.options.webspace);
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
                {this.webspace.allLocalizations.map(({localization}) => (
                    <SingleSelect.Option key={localization} value={localization}>
                        {localization}
                    </SingleSelect.Option>
                ))}
            </SingleSelect>
        );
    }
}

export default CustomUrlsLocaleSelect;
