/* eslint-disable testing-library/prefer-user-event */
// @flow
import {fireEvent, render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import ContactDetails from '../ContactDetails';
import Email from '../../ContactDetails/Email';
import Fax from '../../ContactDetails/Fax';
import Phone from '../../ContactDetails/Phone';
import SocialMedia from '../../ContactDetails/SocialMedia';
import Website from '../../ContactDetails/Website';

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

function getField(label) {
    const labelElement = screen.getByText(label, {selector: 'label'});
    const field = labelElement.closest('.field');

    if (!field) {
        throw new Error(`Expected field for label "${label}"`);
    }

    return field;
}

function getOpenArrowMenu() {
    const menus = document.querySelectorAll('.arrowMenu');
    const menu = menus[menus.length - 1];

    if (!menu) {
        throw new Error('Expected open ArrowMenu');
    }

    return menu;
}

async function clickAddOption(user, option) {
    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));
    await user.click(within(getOpenArrowMenu()).getByRole('button', {name: option}));
}

async function clickTypeOption(user, fieldLabel, optionLabel) {
    const typeButton = getField(fieldLabel).querySelector('button.type');

    if (!typeButton) {
        throw new Error(`Expected type button for "${fieldLabel}"`);
    }

    await user.click(typeButton);
    await user.click(within(getOpenArrowMenu()).getByRole('button', {name: optionLabel}));
}

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
    const user = userEvent.setup();
    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    render(<ContactDetails onBlur={blurSpy} onChange={changeSpy} />);

    await clickAddOption(user, 'sulu_contact.email');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: undefined, emailType: 1}],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [],
    });

    await clickAddOption(user, 'sulu_contact.phone');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [],
        phones: [{phone: undefined, phoneType: 1}],
        socialMedia: [],
        websites: [],
    });

    await clickAddOption(user, 'sulu_contact.fax');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [{fax: undefined, faxType: 1}],
        phones: [],
        socialMedia: [],
        websites: [],
    });

    await clickAddOption(user, 'sulu_contact.website');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [{website: undefined, websiteType: 1}],
    });

    await clickAddOption(user, 'sulu_contact.social_media');
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
    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    render(<ContactDetails onBlur={blurSpy} onChange={changeSpy} />);
    const textboxes = screen.getAllByRole('textbox');

    fireEvent.change(textboxes[0], {
        target: {value: 'test@example.org'},
    });
    expect(changeSpy).toBeCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [],
    });

    fireEvent.change(textboxes[1], {
        target: {value: '1098509'},
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

    fireEvent.click(within(getField('sulu_contact.email')).getByRole('button', {name: 'su-trash-alt'}));
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    fireEvent.click(within(getField('sulu_contact.fax')).getByRole('button', {name: 'su-trash-alt'}));
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{username: 'test', socialMediaType: 1}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    fireEvent.click(within(getField('sulu_contact.phone')).getByRole('button', {name: 'su-trash-alt'}));
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [],
        socialMedia: [{username: 'test', socialMediaType: 1}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    fireEvent.click(within(getField('sulu_contact.social_media')).getByRole('button', {name: 'su-trash-alt'}));
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    fireEvent.click(within(getField('sulu_contact.website')).getByRole('button', {name: 'su-trash-alt'}));
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
    const textboxes = screen.getAllByRole('textbox');

    const emailInput = textboxes[0];
    fireEvent.change(emailInput, {target: {value: 'bla@example.org'}});
    fireEvent.blur(emailInput);
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    const faxInput = textboxes[2];
    fireEvent.change(faxInput, {target: {value: '0923850'}});
    fireEvent.blur(faxInput);
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '0923850', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    const phoneInput = textboxes[1];
    fireEvent.change(phoneInput, {target: {value: '123590'}});
    fireEvent.blur(phoneInput);
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '0923850', faxType: 1}],
        phones: [{phone: '123590', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    const socialMediaInput = textboxes[4];
    fireEvent.change(socialMediaInput, {target: {value: 'bla'}});
    fireEvent.blur(socialMediaInput);
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '0923850', faxType: 1}],
        phones: [{phone: '123590', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'bla'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    const websiteInput = textboxes[3];
    fireEvent.change(websiteInput, {target: {value: 'http://example.org'}});
    fireEvent.blur(websiteInput);
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'bla@example.org', emailType: 1}],
        faxes: [{fax: '0923850', faxType: 1}],
        phones: [{phone: '123590', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'bla'}],
        websites: [{website: 'http://example.org', websiteType: 1}],
    });

    expect(blurSpy).toBeCalledTimes(5);
});

test('Changing the types should call the onChange and onBlur callbacks', async() => {
    const user = userEvent.setup();
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

    await clickTypeOption(user, 'sulu_contact.email', 'Private');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 1}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    await clickTypeOption(user, 'sulu_contact.fax', 'Private');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 1}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    await clickTypeOption(user, 'sulu_contact.phone', 'Private');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 2}],
        socialMedia: [{socialMediaType: 1, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    await clickTypeOption(user, 'sulu_contact.social_media', 'Twitter');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 2}],
        socialMedia: [{socialMediaType: 2, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 1}],
    });

    await clickTypeOption(user, 'sulu_contact.website', 'Private');
    expect(changeSpy).toHaveBeenLastCalledWith({
        emails: [{email: 'test@example.org', emailType: 2}],
        faxes: [{fax: '20937439', faxType: 2}],
        phones: [{phone: '20937439', phoneType: 2}],
        socialMedia: [{socialMediaType: 2, username: 'test'}],
        websites: [{website: 'http://www.example.org', websiteType: 2}],
    });

    expect(blurSpy).toBeCalledTimes(5);
});
