// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import getMockCallArg from 'sulu-admin-bundle/utils/TestHelper/getMockCallArg';
import ContactDetails from '../ContactDetails';
import Email from '../../ContactDetails/Email';
import Fax from '../../ContactDetails/Fax';
import Phone from '../../ContactDetails/Phone';
import SocialMedia from '../../ContactDetails/SocialMedia';
import Website from '../../ContactDetails/Website';

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');

    const FormMock = function FormMock({children}) {
        return React.createElement('div', {'data-testid': 'form'}, children);
    };

    FormMock.Field = function FormFieldMock({children}) {
        return React.createElement('div', {'data-testid': 'form-field'}, children);
    };

    const DropdownButtonMock = function DropdownButtonMock({children, label}) {
        return React.createElement(
            'div',
            {'data-testid': 'dropdown-button'},
            React.createElement('button', {type: 'button'}, label),
            children
        );
    };

    DropdownButtonMock.Item = function DropdownButtonItemMock({children, onClick}) {
        return React.createElement('button', {onClick, type: 'button'}, children);
    };

    return {
        DropdownButton: DropdownButtonMock,
        Form: FormMock,
    };
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../ContactDetails/Email', () => jest.fn(function EmailMock() {
    return <div data-testid="email" />;
}));

jest.mock('../../ContactDetails/Fax', () => jest.fn(function FaxMock() {
    return <div data-testid="fax" />;
}));

jest.mock('../../ContactDetails/Phone', () => jest.fn(function PhoneMock() {
    return <div data-testid="phone" />;
}));

jest.mock('../../ContactDetails/SocialMedia', () => jest.fn(function SocialMediaMock() {
    return <div data-testid="social-media" />;
}));

jest.mock('../../ContactDetails/Website', () => jest.fn(function WebsiteMock() {
    return <div data-testid="website" />;
}));

function getProps(component: any, callIndex: number = 0) {
    return getMockCallArg(component, callIndex, 0);
}

beforeEach(() => {
    jest.clearAllMocks();

    Email.types = [
        {label: 'Work', value: 1},
        {label: 'Private', value: 2},
    ];

    Fax.types = [
        {label: 'Work', value: 1},
        {label: 'Private', value: 2},
    ];

    Phone.types = [
        {label: 'Work', value: 1},
        {label: 'Private', value: 2},
    ];

    SocialMedia.types = [
        {label: 'Facebook', value: 1},
        {label: 'Twitter', value: 2},
    ];

    Website.types = [
        {label: 'Work', value: 1},
        {label: 'Private', value: 2},
    ];
});

test('Render empty ContactDetails', () => {
    const {asFragment} = render(<ContactDetails onBlur={jest.fn()} onChange={jest.fn()} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render empty phone and email fields even if other values are set', () => {
    const value = {
        emails: [],
        faxes: [{fax: '230985230', faxType: 1}],
        phones: [],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.sulu.io', websiteType: 1}],
    };

    const {asFragment} = render(<ContactDetails onBlur={jest.fn()} onChange={jest.fn()} value={value} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render ContactDetails with data', () => {
    const value = {
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 2, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    };

    const {asFragment} = render(<ContactDetails onBlur={jest.fn()} onChange={jest.fn()} value={value} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Add data should call onChange and onBlur callbacks', async() => {
    const blurSpy = jest.fn();
    const changeSpy = jest.fn();
    const user = userEvent.setup();

    render(<ContactDetails onBlur={blurSpy} onChange={changeSpy} />);

    await user.click(screen.getByRole('button', {name: 'sulu_contact.email'}));
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: undefined, emailType: 1}],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [],
    });

    await user.click(screen.getByRole('button', {name: 'sulu_contact.phone'}));
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [],
        phones: [{phone: undefined, phoneType: 1}],
        socialMedia: [],
        websites: [],
    });

    await user.click(screen.getByRole('button', {name: 'sulu_contact.fax'}));
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [{fax: undefined, faxType: 1}],
        phones: [],
        socialMedia: [],
        websites: [],
    });

    await user.click(screen.getByRole('button', {name: 'sulu_contact.website'}));
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [{website: undefined, websiteType: 1}],
    });

    await user.click(screen.getByRole('button', {name: 'sulu_contact.social_media'}));
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [],
        phones: [],
        socialMedia: [{socialMediaType: 1, username: undefined}],
        websites: [],
    });

    expect(blurSpy).toBeCalledTimes(5);
});

test('Add data should also work with predefined email and phone fields', () => {
    const changeSpy = jest.fn();

    render(<ContactDetails onBlur={jest.fn()} onChange={changeSpy} />);

    act(() => {
        getProps(Email).onEmailChange(0, 'test@example.org');
    });

    expect(changeSpy).toBeCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [],
    });

    act(() => {
        getProps(Phone).onPhoneChange(0, '1098509');
    });

    expect(changeSpy).toBeCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [],
        phones: [{phone: '1098509', phoneType: 1}],
        socialMedia: [],
        websites: [],
    });
});

test('Remove data should call the onChange and onBlur callbacks', () => {
    const value = {
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{username: 'test', socialMediaType: 1}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    };

    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    render(<ContactDetails onBlur={blurSpy} onChange={changeSpy} value={value} />);

    act(() => {
        getProps(Email).onRemove(0);
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    act(() => {
        getProps(Fax).onRemove(0);
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{username: 'test', socialMediaType: 1}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    act(() => {
        getProps(Phone).onRemove(0);
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [],
        socialMedia: [{username: 'test', socialMediaType: 1}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    act(() => {
        getProps(SocialMedia).onRemove(0);
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    act(() => {
        getProps(Website).onRemove(0);
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [],
    });

    expect(blurSpy).toBeCalledTimes(5);
});

test('Edit data should call the onChange and onBlur callbacks', () => {
    const value = {
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    };

    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    render(<ContactDetails onBlur={blurSpy} onChange={changeSpy} value={value} />);

    act(() => {
        getProps(Email).onEmailChange(0, 'bla@example.org');
        getProps(Email).onBlur();
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    act(() => {
        getProps(Fax).onFaxChange(0, '0923850');
        getProps(Fax).onBlur();
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '0923850', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    act(() => {
        getProps(Phone).onPhoneChange(0, '123590');
        getProps(Phone).onBlur();
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '0923850', faxType: 1}],
        phones: [{phone: '123590', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    act(() => {
        getProps(SocialMedia).onUsernameChange(0, 'bla');
        getProps(SocialMedia).onBlur();
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '0923850', faxType: 1}],
        phones: [{phone: '123590', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'bla'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    act(() => {
        getProps(Website).onWebsiteChange(0, 'http://example.org');
        getProps(Website).onBlur();
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '0923850', faxType: 1}],
        phones: [{phone: '123590', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'bla'}],
        websites: [{website: 'http://example.org', websiteType: 1}],
    });

    expect(blurSpy).toBeCalledTimes(5);
});

test('Changing the types should call the onChange and onBlur callbacks', () => {
    const value = {
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    };

    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    render(<ContactDetails onBlur={blurSpy} onChange={changeSpy} value={value} />);

    act(() => {
        getProps(Email).onTypeChange(0, 2);
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    act(() => {
        getProps(Fax).onTypeChange(0, 2);
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    act(() => {
        getProps(Phone).onTypeChange(0, 2);
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 2}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    act(() => {
        getProps(SocialMedia).onTypeChange(0, 2);
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 2}],
        socialMedia: [{socialMediaType: 2, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    act(() => {
        getProps(Website).onTypeChange(0, 2);
    });
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 2}],
        socialMedia: [{socialMediaType: 2, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 2}],
    });

    expect(blurSpy).toBeCalledTimes(5);
});
