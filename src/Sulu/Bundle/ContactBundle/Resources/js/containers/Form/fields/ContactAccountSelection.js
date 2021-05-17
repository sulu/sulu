// @flow
import React from 'react';
import ContactAccountSelectionComponent from '../../ContactAccountSelection';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';

class ContactAccountSelection extends React.Component<FieldTypeProps<Array<string>>> {
    handleChange = (value: Array<Object>) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    handleItemClick = (itemId: string) => {
        const {router} = this.props;

        if (!router || !itemId) {
            return;
        }

        router.navigate(
            itemId.startsWith('c') ? 'sulu_contact.contact_edit_form' : 'sulu_contact.account_edit_form',
            {id: itemId.substr(1)}
        );
    };

    render() {
        const {disabled, value} = this.props;

        return (
            <ContactAccountSelectionComponent
                disabled={disabled === null ? undefined : disabled}
                onChange={this.handleChange}
                onItemClick={this.handleItemClick}
                value={value === null ? undefined : value}
            />
        );
    }
}

export default ContactAccountSelection;
