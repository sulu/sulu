// @flow
import React from 'react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import ContactAccountSelectionComponent from '../../ContactAccountSelection';

class ContactAccountSelection extends React.Component<FieldTypeProps<Array<string>>> {
    handleChange = (value: Array<Object>) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, value} = this.props;

        return (
            <ContactAccountSelectionComponent
                disabled={disabled === null ? undefined : disabled}
                onChange={this.handleChange}
                value={value === null ? undefined : value}
            />
        );
    }
}

export default ContactAccountSelection;
