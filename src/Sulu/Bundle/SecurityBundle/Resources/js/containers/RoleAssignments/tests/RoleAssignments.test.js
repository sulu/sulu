// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {localizationStore} from 'sulu-admin-bundle/stores';
import RoleAssignments from '../RoleAssignments';

jest.mock('sulu-admin-bundle/stores/ResourceListStore', () => jest.fn().mockImplementation(
    function() {
        this.loading = false;
        this.data = [
            {
                id: 2,
                name: 'Role Name 2',
                system: 'Sulu',
            },
            {
                id: 5,
                name: 'Role Name 5',
                system: 'Sulu',
            },
            {
                id: 23,
                name: 'Role Name 23',
                system: 'Sulu',
            },
        ];
    }
));

jest.mock('sulu-admin-bundle/stores', () => ({
    localizationStore: {
        localizations: undefined,
    },
}));

jest.mock('sulu-admin-bundle/utils/Translator');

test('Render component without data', () => {
    localizationStore.localizations = [
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
    render(
        <RoleAssignments
            onChange={jest.fn()}
            value={[]}
        />
    );

    expect(screen.getByRole('button', {name: /^sulu_admin\.none_selected/})).toBeEnabled();
    expect(screen.queryByRole('table')).not.toBeInTheDocument();
});

test('Render component', () => {
    const value: Array<Object> = [
        {
            id: 1,
            role: {
                id: 5,
                name: 'Role Name 5',
                system: 'Sulu',
            },
            locales: ['de', 'en'],
        },
        {
            id: 2,
            role: {
                id: 23,
                name: 'Role Name 23',
                system: 'Sulu',
            },
            locales: ['de'],
        },
    ];

    localizationStore.localizations = [
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

    render(
        <RoleAssignments
            onChange={jest.fn()}
            value={value}
        />
    );

    expect(screen.getByRole('button', {name: /^Role Name 5, Role Name 23/})).toBeEnabled();
    expect(screen.getByRole('button', {name: /^sulu_admin\.all_selected/})).toBeEnabled();
    expect(screen.getByRole('button', {name: /^de/})).toBeEnabled();
    expect(screen.getAllByRole('row')).toHaveLength(2);
});

test('Render component in disabled state', () => {
    const value: Array<Object> = [
        {
            id: 1,
            role: {
                id: 5,
                name: 'Role Name 5',
                system: 'Sulu',
            },
            locales: ['de', 'en'],
        },
        {
            id: 2,
            role: {
                id: 23,
                name: 'Role Name 23',
                system: 'Sulu',
            },
            locales: ['de'],
        },
    ];

    localizationStore.localizations = [
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

    render(
        <RoleAssignments
            disabled={true}
            onChange={jest.fn()}
            value={value}
        />
    );

    expect(screen.getByRole('button', {name: /^Role Name 5, Role Name 23/})).toBeDisabled();
    expect(screen.getByRole('button', {name: /^sulu_admin\.all_selected/})).toBeDisabled();
    expect(screen.getByRole('button', {name: /^de/})).toBeDisabled();
});

test('Should trigger onChange correctly when MultiSelect for roles changes', async() => {
    const user = userEvent.setup();
    const value: Array<Object> = [
        {
            id: 1,
            role: {
                id: 5,
                name: 'Role Name 5',
                system: 'Sulu',
            },
            locales: ['de', 'en'],
        },
        {
            id: 2,
            role: {
                id: 23,
                name: 'Role Name 23',
                system: 'Sulu',
            },
            locales: ['de'],
        },
    ];

    localizationStore.localizations = [
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
    render(
        <RoleAssignments
            onChange={onChangeSpy}
            value={value}
        />
    );

    await user.click(screen.getByRole('button', {name: /^Role Name 5, Role Name 23/}));
    await user.click(screen.getByRole('button', {name: /Role Name 2$/}));

    const newValue: Array<Object> = [
        {
            id: 1,
            role: {
                id: 5,
                name: 'Role Name 5',
                system: 'Sulu',
            },
            locales: ['de', 'en'],
        },
        {
            id: 2,
            role: {
                id: 23,
                name: 'Role Name 23',
                system: 'Sulu',
            },
            locales: ['de'],
        },
        {
            role: {
                id: 2,
                name: 'Role Name 2',
                system: 'Sulu',
            },
            locales: [],
        },
    ];

    expect(onChangeSpy).toHaveBeenCalledWith(newValue);
});

test('Should trigger onChange correctly when RoleAssignment changes', async() => {
    const user = userEvent.setup();
    const value: Array<Object> = [
        {
            id: 1,
            role: {
                id: 5,
                name: 'Role Name 5',
                system: 'Sulu',
            },
            locales: ['de', 'en'],
        },
        {
            id: 2,
            role: {
                id: 23,
                name: 'Role Name 23',
                system: 'Sulu',
            },
            locales: ['de'],
        },
    ];

    localizationStore.localizations = [
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
    render(
        <RoleAssignments
            onChange={onChangeSpy}
            value={value}
        />
    );

    const newValue: Array<Object> = [
        {
            id: 1,
            role: {
                id: 5,
                name: 'Role Name 5',
                system: 'Sulu',
            },
            locales: ['de'],
        },
        {
            id: 2,
            role: {
                id: 23,
                name: 'Role Name 23',
                system: 'Sulu',
            },
            locales: ['de'],
        },
    ];
    await user.click(screen.getByRole('button', {name: /^sulu_admin\.all_selected/}));
    await user.click(screen.getByRole('button', {name: /en$/}));

    expect(onChangeSpy).toHaveBeenCalledWith(newValue);
});
