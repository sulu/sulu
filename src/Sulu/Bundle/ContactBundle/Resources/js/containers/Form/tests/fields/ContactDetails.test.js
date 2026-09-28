// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import Email from '../../../../components/ContactDetails/Email';
import Phone from '../../../../components/ContactDetails/Phone';
import ContactDetails from '../../fields/ContactDetails';

jest.mock('sulu-admin-bundle/utils/Translator');

beforeEach(() => {
    Email.types = [
        {label: 'Work', value: 1},
    ];

    Phone.types = [
        {label: 'Work', value: 1},
    ];
});

test('Pass props correctly to ContactDetails component', () => {
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();
    const value = {
        emails: [],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [],
    };

    render(
        <ContactDetails
            {...fieldTypeDefaultProps}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    expect(screen.getByText('sulu_contact.contact_details')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /sulu_admin\.add/})).toBeInTheDocument();
});

test('Call onChange and onFinish when a new email entry is added', async() => {
    const user = userEvent.setup();
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();
    const value = {
        emails: [],
        faxes: [],
        phones: [],
        socialMedia: [],
        websites: [],
    };

    render(
        <ContactDetails
            {...fieldTypeDefaultProps}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    await user.click(screen.getByRole('button', {name: /sulu_admin\.add/}));
    await user.click(screen.getByText('sulu_contact.email', {selector: 'button.action'}));

    expect(changeSpy).toHaveBeenCalled();
    expect(finishSpy).toHaveBeenCalled();
});

test('Pass undefined as value if null is given', () => {
    render(
        <ContactDetails
            {...fieldTypeDefaultProps}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={null}
        />
    );

    expect(screen.getAllByRole('textbox')).toHaveLength(2);
    expect(screen.getByText('sulu_contact.email')).toBeInTheDocument();
    expect(screen.getByText('sulu_contact.phone')).toBeInTheDocument();
});
