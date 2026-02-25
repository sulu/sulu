// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import RoleAssignment from '../RoleAssignment';
import type {Localization} from 'sulu-admin-bundle/stores';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

const value = {
    id: 1,
    role: {
        id: 5,
        name: 'Role Name 5',
        system: 'Sulu',
    },
    locales: ['de'],
};

const localizations: Array<Localization> = [
    {
        country: '',
        default: '1',
        language: 'en',
        locale: 'en',
        localization: 'en',
        shadow: '',
    },
    {
        country: '',
        default: '0',
        language: 'de',
        locale: 'de',
        localization: 'de',
        shadow: '',
    },
];

test('Render component', () => {
    const {container} = render(
        <table>
            <tbody>
                <RoleAssignment
                    localizations={localizations}
                    onChange={jest.fn()}
                    value={value}
                />
            </tbody>
        </table>
    );

    expect(container).toMatchSnapshot();
});

test('Render component in disabled state', () => {
    const {container} = render(
        <table>
            <tbody>
                <RoleAssignment
                    disabled={true}
                    localizations={localizations}
                    onChange={jest.fn()}
                    value={value}
                />
            </tbody>
        </table>
    );

    expect(container).toMatchSnapshot();
});

test('The component should trigger the change callback', async() => {
    const user = userEvent.setup();
    const onChangeSpy = jest.fn();

    render(
        <table>
            <tbody>
                <RoleAssignment
                    localizations={localizations}
                    onChange={onChangeSpy}
                    value={value}
                />
            </tbody>
        </table>
    );

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: /en$/}));

    const expectedValue = {
        id: 1,
        role: {
            id: 5,
            name: 'Role Name 5',
            system: 'Sulu',
        },
        locales: ['de', 'en'],
    };
    expect(onChangeSpy).toHaveBeenCalledWith(expectedValue);
});
