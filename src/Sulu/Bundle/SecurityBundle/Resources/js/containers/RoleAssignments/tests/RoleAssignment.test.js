// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import RoleAssignment from '../RoleAssignment';
import type {Localization} from 'sulu-admin-bundle/stores';

jest.mock('sulu-admin-bundle/utils/Translator');

function getDisplayButton(container: HTMLElement): HTMLElement {
    const displayButton = container.querySelector('button.displayValue');

    if (!(displayButton instanceof HTMLElement)) {
        throw new Error('Expected display value button');
    }

    return displayButton;
}

function renderRoleAssignment(props: Object = {}) {
    const body = document.body;
    const table = document.createElement('table');
    const tbody = document.createElement('tbody');

    if (!body) {
        throw new Error('Expected document body');
    }

    table.appendChild(tbody);
    body.appendChild(table);

    return render(<RoleAssignment {...props} />, {container: tbody});
}

test('Render component', () => {
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

    const {container} = renderRoleAssignment({
        localizations,
        onChange: jest.fn(),
        value,
    });

    expect(container).toMatchSnapshot();
});

test('Render component in disabled state', () => {
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

    const {container} = renderRoleAssignment({
        disabled: true,
        localizations,
        onChange: jest.fn(),
        value,
    });

    expect(container).toMatchSnapshot();
});

test('The component should trigger the change callback', async() => {
    const user = userEvent.setup();
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

    const onChangeSpy = jest.fn();
    const {container} = renderRoleAssignment({
        localizations,
        onChange: onChangeSpy,
        value,
    });

    await user.click(getDisplayButton(container));
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
