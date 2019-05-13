// @flow
import React from 'react';
import {toJS} from 'mobx';
import {observer} from 'mobx-react';
import {DropdownButton, Form} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import Email from './Email';
import Fax from './Fax';
import Phone from './Phone';
import SocialMedia from './SocialMedia';
import Website from './Website';
import type {ContactDetailsValue} from './types';

type Props = {|
    onBlur: () => void,
    onChange: (value: ContactDetailsValue) => void,
    value: ContactDetailsValue,
|};

@observer
export default class ContactDetails extends React.Component<Props> {
    static defaultProps = {
        value: {
            emails: [],
            faxes: [],
            phones: [],
            socialMedia: [],
            websites: [],
        },
    };

    addEntry = (type: string, entry: Object) => {
        const {onBlur, onChange, value} = this.props;
        onChange({...value, [type]: value[type].concat(entry)});
        onBlur();
    };

    handleEmailAddClick = () => {
        this.addEntry('emails', {email: undefined});
    };

    handlePhoneAddClick = () => {
        this.addEntry('phones', {phone: undefined});
    };

    handleWebsiteAddClick = () => {
        this.addEntry('websites', {website: undefined});
    };

    handleFaxAddClick = () => {
        this.addEntry('faxes', {fax: undefined});
    };

    handleSocialMediaAddClick = () => {
        this.addEntry('socialMedia', {username: undefined});
    };

    updateValue = (type: string, index: number, property: string, updatedValue: ?string) => {
        const {onChange, value} = this.props;

        const newValue = toJS(value);
        newValue[type][index][property] = updatedValue;

        onChange(newValue);
    };

    handleEmailChange = (index: number, email: ?string) => {
        this.updateValue('emails', index, 'email', email);
    };

    handlePhoneChange = (index: number, phone: ?string) => {
        this.updateValue('phones', index, 'phone', phone);
    };

    handleWebsiteChange = (index: number, website: ?string) => {
        this.updateValue('websites', index, 'website', website);
    };

    handleFaxChange = (index: number, fax: ?string) => {
        this.updateValue('faxes', index, 'fax', fax);
    };

    handleUsernameChange = (index: number, username: ?string) => {
        this.updateValue('socialMedia', index, 'username', username);
    };

    removeEntry = (type: string, removeIndex: number) => {
        const {onBlur, onChange, value} = this.props;

        onChange({...value, [type]: value[type].filter((email, index) => index !== removeIndex)});
        onBlur();
    };

    handleEmailRemove = (removeIndex: number) => {
        this.removeEntry('emails', removeIndex);
    };

    handlePhoneRemove = (removeIndex: number) => {
        this.removeEntry('phones', removeIndex);
    };

    handleWebsiteRemove = (removeIndex: number) => {
        this.removeEntry('websites', removeIndex);
    };

    handleFaxRemove = (removeIndex: number) => {
        this.removeEntry('faxes', removeIndex);
    };

    handleSocialMediaRemove = (removeIndex: number) => {
        this.removeEntry('socialMedia', removeIndex);
    };

    render() {
        const {onBlur, value} = this.props;

        return (
            <Form>
                {value.emails.map((email, index) => (
                    <Email
                        email={email.email}
                        index={index}
                        key={index}
                        onBlur={onBlur}
                        onEmailChange={this.handleEmailChange}
                        onRemove={this.handleEmailRemove}
                    />
                ))}
                {value.phones.map((phone, index) => (
                    <Phone
                        index={index}
                        key={index}
                        onBlur={onBlur}
                        onPhoneChange={this.handlePhoneChange}
                        onRemove={this.handlePhoneRemove}
                        phone={phone.phone}
                    />
                ))}
                {value.websites.map((website, index) => (
                    <Website
                        index={index}
                        key={index}
                        onBlur={onBlur}
                        onRemove={this.handleWebsiteRemove}
                        onWebsiteChange={this.handleWebsiteChange}
                        website={website.website}
                    />
                ))}
                {value.faxes.map((fax, index) => (
                    <Fax
                        fax={fax.fax}
                        index={index}
                        key={index}
                        onBlur={onBlur}
                        onFaxChange={this.handleFaxChange}
                        onRemove={this.handleFaxRemove}
                    />
                ))}
                {value.socialMedia.map((socialMedia, index) => (
                    <SocialMedia
                        index={index}
                        key={index}
                        onBlur={onBlur}
                        onRemove={this.handleSocialMediaRemove}
                        onUsernameChange={this.handleUsernameChange}
                        username={socialMedia.username}
                    />
                ))}
                <Form.Field colSpan={4} label={translate('sulu_contact.contact_details')}>
                    <DropdownButton icon="su-plus" label={translate('sulu_admin.add')}>
                        <DropdownButton.Item onClick={this.handleEmailAddClick}>
                            {translate('sulu_contact.email')}
                        </DropdownButton.Item>
                        <DropdownButton.Item onClick={this.handlePhoneAddClick}>
                            {translate('sulu_contact.phone')}
                        </DropdownButton.Item>
                        <DropdownButton.Item onClick={this.handleWebsiteAddClick}>
                            {translate('sulu_contact.website')}
                        </DropdownButton.Item>
                        <DropdownButton.Item onClick={this.handleFaxAddClick}>
                            {translate('sulu_contact.fax')}
                        </DropdownButton.Item>
                        <DropdownButton.Item onClick={this.handleSocialMediaAddClick}>
                            {translate('sulu_contact.social_media')}
                        </DropdownButton.Item>
                    </DropdownButton>
                </Form.Field>
            </Form>
        );
    }
}
