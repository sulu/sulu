// @flow
import React from 'react';
import {computed, toJS} from 'mobx';
import {observer} from 'mobx-react';
import {DropdownButton, Form} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import Email from './Email';
import Fax from './Fax';
import Phone from './Phone';
import SocialMedia from './SocialMedia';
import Website from './Website';
import type {ContactDetailsValue, Email as EmailType, Phone as PhoneType} from './types';

type Props = {|
    onBlur: () => void,
    onChange: (value: ContactDetailsValue) => void,
    value: ContactDetailsValue,
|};

@observer
class ContactDetails extends React.Component<Props> {
    static defaultProps = {
        value: {
            emails: [],
            faxes: [],
            phones: [],
            socialMedia: [],
            websites: [],
        },
    };

    getEmptyEntry(type: string) {
        switch (type) {
            case 'emails':
                return {email: undefined, emailType: Email.types[0].value};
            case 'phones':
                return {phone: undefined, phoneType: Phone.types[0].value};
            case 'websites':
                return {website: undefined, websiteType: Website.types[0].value};
            case 'faxes':
                return {fax: undefined, faxType: Fax.types[0].value};
            case 'socialMedia':
                return {socialMediaType: SocialMedia.types[0].value, username: undefined};
        }
    }

    addEntry = (type: string) => {
        const {onBlur, onChange, value} = this.props;
        onChange({...value, [type]: value[type].concat(this.getEmptyEntry(type))});
        onBlur();
    };

    handleEmailAddClick = () => {
        this.addEntry('emails');
    };

    handlePhoneAddClick = () => {
        this.addEntry('phones');
    };

    handleWebsiteAddClick = () => {
        this.addEntry('websites');
    };

    handleFaxAddClick = () => {
        this.addEntry('faxes');
    };

    handleSocialMediaAddClick = () => {
        this.addEntry('socialMedia');
    };

    updateValue = (type: string, index: number, property: string, updatedValue: ?string | number) => {
        const {onChange, value} = this.props;

        const newValue = toJS(value);
        const typeEntries = newValue[type];
        if (typeEntries[index] === undefined) {
            typeEntries[index] = this.getEmptyEntry(type);
        }

        typeEntries[index][property] = updatedValue;

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

    handleEmailTypeChange = (index: number, type: number) => {
        this.updateValue('emails', index, 'emailType', type);
        this.props.onBlur();
    };

    handlePhoneTypeChange = (index: number, type: number) => {
        this.updateValue('phones', index, 'phoneType', type);
        this.props.onBlur();
    };

    handleWebsiteTypeChange = (index: number, type: number) => {
        this.updateValue('websites', index, 'websiteType', type);
        this.props.onBlur();
    };

    handleFaxTypeChange = (index: number, type: number) => {
        this.updateValue('faxes', index, 'faxType', type);
        this.props.onBlur();
    };

    handleSocialMediaTypeChange = (index: number, type: number) => {
        this.updateValue('socialMedia', index, 'socialMediaType', type);
        this.props.onBlur();
    };

    @computed get emails(): Array<EmailType> {
        const {value: {emails}} = this.props;

        if (emails.length === 0) {
            // $FlowFixMe
            return [
                this.getEmptyEntry('emails'),
            ];
        }

        return emails;
    }

    @computed get phones(): Array<PhoneType> {
        const {value: {phones}} = this.props;

        if (phones.length === 0) {
            // $FlowFixMe
            return [
                this.getEmptyEntry('phones'),
            ];
        }

        return phones;
    }

    render() {
        const {onBlur, value} = this.props;

        const {faxes, socialMedia, websites} = value;

        return (
            <Form>
                {this.emails.map((email, index) => (
                    <Email
                        email={email.email}
                        index={index}
                        key={index}
                        onBlur={onBlur}
                        onEmailChange={this.handleEmailChange}
                        onRemove={this.handleEmailRemove}
                        onTypeChange={this.handleEmailTypeChange}
                        type={email.emailType}
                    />
                ))}
                {this.phones.map((phone, index) => (
                    <Phone
                        index={index}
                        key={index}
                        onBlur={onBlur}
                        onPhoneChange={this.handlePhoneChange}
                        onRemove={this.handlePhoneRemove}
                        onTypeChange={this.handlePhoneTypeChange}
                        phone={phone.phone}
                        type={phone.phoneType}
                    />
                ))}
                {faxes.map((fax, index) => (
                    <Fax
                        fax={fax.fax}
                        index={index}
                        key={index}
                        onBlur={onBlur}
                        onFaxChange={this.handleFaxChange}
                        onRemove={this.handleFaxRemove}
                        onTypeChange={this.handleFaxTypeChange}
                        type={fax.faxType}
                    />
                ))}
                {websites.map((website, index) => (
                    <Website
                        index={index}
                        key={index}
                        onBlur={onBlur}
                        onRemove={this.handleWebsiteRemove}
                        onTypeChange={this.handleWebsiteTypeChange}
                        onWebsiteChange={this.handleWebsiteChange}
                        type={website.websiteType}
                        website={website.website}
                    />
                ))}
                {socialMedia.map((socialMedia, index) => (
                    <SocialMedia
                        index={index}
                        key={index}
                        onBlur={onBlur}
                        onRemove={this.handleSocialMediaRemove}
                        onTypeChange={this.handleSocialMediaTypeChange}
                        onUsernameChange={this.handleUsernameChange}
                        type={socialMedia.socialMediaType}
                        username={socialMedia.username}
                    />
                ))}
                <Form.Field colSpan={6} label={translate('sulu_contact.contact_details')}>
                    <DropdownButton icon="su-plus" label={translate('sulu_admin.add')}>
                        <DropdownButton.Item onClick={this.handleEmailAddClick}>
                            {translate('sulu_contact.email')}
                        </DropdownButton.Item>
                        <DropdownButton.Item onClick={this.handlePhoneAddClick}>
                            {translate('sulu_contact.phone')}
                        </DropdownButton.Item>
                        <DropdownButton.Item onClick={this.handleFaxAddClick}>
                            {translate('sulu_contact.fax')}
                        </DropdownButton.Item>
                        <DropdownButton.Item onClick={this.handleWebsiteAddClick}>
                            {translate('sulu_contact.website')}
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

export default ContactDetails;
