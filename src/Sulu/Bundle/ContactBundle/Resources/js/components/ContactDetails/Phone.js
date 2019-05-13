// @flow
import React from 'react';
import {Phone as PhoneComponent} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import Field from './Field';

type Props = {|
    index: number,
    onBlur: () => void,
    onPhoneChange: (index: number, phone: ?string) => void,
    onRemove: (index: number) => void,
    phone: ?string,
|};

export default class Phone extends React.Component<Props> {
    handlePhoneChange = (phone: ?string) => {
        const {index, onPhoneChange} = this.props;

        onPhoneChange(index, phone);
    };

    render() {
        const {index, onBlur, onRemove, phone} = this.props;

        return (
            <Field index={index} label={translate('sulu_contact.phone')} onRemove={onRemove}>
                <PhoneComponent onBlur={onBlur} onChange={this.handlePhoneChange} value={phone} />
            </Field>
        );
    }
}
