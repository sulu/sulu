// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {MultiSelect} from 'sulu-admin-bundle/containers';
import roleAssignmentsStyle from './roleAssignments.scss';

type Props = {
    onChange: (value: Object) => void,
    value: Object,
};

@observer
export default class RoleAssignments extends React.Component<Props> {
    handleLocalizationChange = (newLocalizations: Array<string>) => {
        const newValue = {...this.props.value};
        newValue.locales = newLocalizations;

        this.props.onChange(newValue);
    };

    render() {
        const {value} = this.props;

        console.log(value);

        return (
            <div className={roleAssignmentsStyle.roleAssignmentContainer}>
                <div>{value.role.name}</div>
                <div>{value.role.system}</div>
                <div>
                    <MultiSelect
                        displayProperty={'locale'}
                        idProperty={'locale'}
                        onChange={this.handleLocalizationChange}
                        resourceKey={'localizations'}
                        values={value.locales}
                    />
                </div>
            </div>
        );
    }
}
