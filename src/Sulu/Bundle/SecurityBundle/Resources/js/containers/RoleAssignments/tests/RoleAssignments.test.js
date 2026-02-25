// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {localizationStore} from 'sulu-admin-bundle/stores';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import RoleAssignments from '../RoleAssignments';

jest.mock('sulu-admin-bundle/containers', () => ({
    ResourceMultiSelect: jest.fn(() => null),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    localizationStore: {
        localizations: undefined,
    },
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

const containersMock = ((jest.requireMock('sulu-admin-bundle/containers'): any): {
    ResourceMultiSelect: {mock: {calls: Array<[Object]>}},
    ...
});

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render component without data', () => {
    localizationStore.localizations = [
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
    const {asFragment} = render(
        <RoleAssignments
            onChange={jest.fn()}
            value={[]}
        />
    );

    expect(asFragment()).toMatchSnapshot();
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

    const {asFragment} = render(
        <RoleAssignments
            onChange={jest.fn()}
            value={value}
        />
    );

    expect(asFragment()).toMatchSnapshot();
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

    const {asFragment} = render(
        <RoleAssignments
            disabled={true}
            onChange={jest.fn()}
            value={value}
        />
    );

    expect(asFragment()).toMatchSnapshot();
    expect(getLatestMockProps(containersMock.ResourceMultiSelect).disabled).toEqual(true);
});

test('Should trigger onChange correctly when MultiSelect for roles changes', () => {
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

    const onChangeSpy = jest.fn();
    render(
        <RoleAssignments
            onChange={onChangeSpy}
            value={value}
        />
    );

    getLatestMockProps(containersMock.ResourceMultiSelect).onChange(
        [2, 5, 23],
        [
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
        ]
    );

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

    expect(onChangeSpy).toBeCalledWith(newValue);
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

    const firstRoleRow = screen.getByText('Role Name 5').closest('tr');
    if (!firstRoleRow) {
        throw new Error('Expected role row to exist');
    }

    await user.click(within(firstRoleRow).getByRole('button'));
    await user.click(screen.getByRole('button', {name: /en$/}));

    expect(onChangeSpy).toBeCalledWith(newValue);
});
