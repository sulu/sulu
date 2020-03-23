// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {SingleSelect} from 'sulu-admin-bundle/components';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {webspaceStore} from 'sulu-page-bundle/stores';

@observer
class CustomUrlsLocaleSelect extends React.Component<FieldTypeProps<string>> {
    handleChange = (value: string) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, formInspector, value} = this.props;

        return (
            <SingleSelect
                disabled={!!disabled}
                onChange={this.handleChange}
                value={value}
            >
                {webspaceStore.getWebspace(formInspector.options.webspace).allLocalizations.map(({localization}) => (
                    <SingleSelect.Option key={localization} value={localization}>
                        {localization}
                    </SingleSelect.Option>
                ))}
            </SingleSelect>
        );
    }
}

export default CustomUrlsLocaleSelect;
