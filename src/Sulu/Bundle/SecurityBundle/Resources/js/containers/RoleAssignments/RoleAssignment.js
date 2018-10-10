// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {MultiSelect} from 'sulu-admin-bundle/components';
import type {Localization} from 'sulu-admin-bundle/stores';
import roleAssignmentStyle from './roleAssignment.scss';

type Props = {
    localizations: Array<Localization>,
    onChange: (value: Object) => void,
    value: Object,
};

@observer
export default class RoleAssignment extends React.Component<Props> {
    handleChange = (newLocalizations: Array<string>) => {
        const newValue = {...this.props.value};
        newValue.locales = newLocalizations;

        this.props.onChange(newValue);
    };

    render() {
        const {localizations, value} = this.props;

        return (
            <div className={roleAssignmentStyle.roleAssignmentContainer}>
                <div>{value.role.name}</div>
                <div>{value.role.system}</div>
                <div>
                    <MultiSelect
                        onChange={this.handleChange}
                        values={value.locales}
                    >
                        {localizations.map((localization, index) => (
                            <MultiSelect.Option key={index} value={localization.locale}>
                                {localization.locale}
                            </MultiSelect.Option>
                        ))}
                    </MultiSelect>
                </div>
            </div>
        );
    }
}
