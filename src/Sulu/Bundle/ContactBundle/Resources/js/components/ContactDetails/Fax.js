// @flow
import React from 'react';
import {Phone as PhoneComponent} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import Field from './Field';

type Props = {|
    fax: ?string,
    index: number,
    onBlur: () => void,
    onFaxChange: (index: number, fax: ?string) => void,
    onRemove: (index: number) => void,
|};

export default class Fax extends React.Component<Props> {
    handleFaxChange = (fax: ?string) => {
        const {index, onFaxChange} = this.props;

        onFaxChange(index, fax);
    };

    render() {
        const {fax, index, onBlur, onRemove} = this.props;

        return (
            <Field index={index} label={translate('sulu_contact.fax')} onRemove={onRemove}>
                <PhoneComponent onBlur={onBlur} onChange={this.handleFaxChange} value={fax} />
            </Field>
        );
    }
}
