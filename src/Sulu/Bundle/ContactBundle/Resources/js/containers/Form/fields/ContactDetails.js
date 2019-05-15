// @flow
import React from 'react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import ContactDetailsComponent from '../../../components/ContactDetails';
import type {ContactDetailsValue} from '../../../components/ContactDetails/types';

export default class ContactDetails extends React.Component<FieldTypeProps<ContactDetailsValue>> {
    render() {
        const {onChange, onFinish, value} = this.props;

        return (
            <ContactDetailsComponent onBlur={onFinish} onChange={onChange} value={value !== null ? value : undefined} />
        );
    }
}
