// @flow
import React from 'react';
import {Input} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import Field from './Field';

type Props = {|
    index: number,
    onBlur: () => void,
    onRemove: (index: number) => void,
    onUsernameChange: (index: number, fax: ?string) => void,
    username: ?string,
|};

export default class SocialMedia extends React.Component<Props> {
    handleInputChange = (username: ?string) => {
        const {index, onUsernameChange} = this.props;

        onUsernameChange(index, username);
    };

    render() {
        const {index, onBlur, onRemove, username} = this.props;

        return (
            <Field index={index} label={translate('sulu_contact.social_media')} onRemove={onRemove}>
                <Input icon="su-user" onBlur={onBlur} onChange={this.handleInputChange} value={username} />
            </Field>
        );
    }
}
