// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import {MultiSelect} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import webspaceStore from '../../../stores/webspaceStore';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';

@observer
class PageSettingsNavigationSelect extends React.Component<FieldTypeProps<Array<string | number>>> {
    @computed get webspace() {
        const {formInspector} = this.props;

        return webspaceStore.getWebspace(formInspector.options.webspace);
    }

    handleChange = (value: Array<string | number>) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, value} = this.props;

        return (
            <MultiSelect
                allSelectedText={translate('sulu_page.all_navigations')}
                disabled={!!disabled}
                noneSelectedText={translate('sulu_page.no_navigation')}
                onChange={this.handleChange}
                values={value || []}
            >
                {this.webspace.navigations.map(({key, title}) => (
                    <MultiSelect.Option key={key} value={key}>
                        {title}
                    </MultiSelect.Option>
                ))}
            </MultiSelect>
        );
    }
}

export default PageSettingsNavigationSelect;
