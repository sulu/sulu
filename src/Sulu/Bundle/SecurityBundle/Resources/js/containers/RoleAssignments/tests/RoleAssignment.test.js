// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import RoleAssignment from '../RoleAssignment';
import type {Localization} from 'sulu-admin-bundle/stores';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

function createValue() {
    return {
        id: 1,
        role: {
            id: 5,
            name: 'Role Name 5',
            system: 'Sulu',
        },
        locales: ['de'],
    };
}

function createLocalizations(): Array<Localization> {
    return [
        {
            country: '',
            default: '1',
            language: 'en',
            locale: 'en',
            localization: 'en',
            shadow: '',
            xDefault: '',
        },
        {
            country: '',
            default: '0',
            language: 'de',
            locale: 'de',
            localization: 'de',
            shadow: '',
            xDefault: '',
        },
    ];
}

function renderRoleAssignment(props: Object = {}) {
    return render(
        <table>
            <tbody>
                <RoleAssignment
                    localizations={createLocalizations()}
                    onChange={jest.fn()}
                    value={createValue()}
                    {...props}
                />
            </tbody>
        </table>
    );
}

test('Render component', () => {
    renderRoleAssignment();

    expect(screen.getByText('Role Name 5')).toBeInTheDocument();
    expect(screen.getByText('Sulu')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /^de/})).toBeInTheDocument();
});

test('Render component in disabled state', () => {
    renderRoleAssignment({disabled: true});

    expect(screen.getByRole('button', {name: /^de/})).toBeDisabled();
});

test('The component should trigger the change callback', async() => {
    const user = userEvent.setup();
    const onChangeSpy = jest.fn();

    renderRoleAssignment({onChange: onChangeSpy});

    await user.click(screen.getByRole('button', {name: /^de/}));
    await user.click(screen.getByRole('button', {name: /^en/}));

    expect(onChangeSpy).toHaveBeenCalledWith({
        id: 1,
        role: {
            id: 5,
            name: 'Role Name 5',
            system: 'Sulu',
        },
        locales: ['de', 'en'],
    });
});
