// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {localizationStore} from 'sulu-admin-bundle/stores';
import {ResourceMultiSelect} from 'sulu-admin-bundle/containers';
import {findAllElementsByType, findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import RoleAssignments from '../RoleAssignments';
import RoleAssignment from '../RoleAssignment';

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
    const {container} = render(
        <RoleAssignments
            onChange={jest.fn()}
            value={[]}
        />
    );

    expect(container.innerHTML).toMatchSnapshot();
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

    const {container} = render(
        <RoleAssignments
            onChange={jest.fn()}
            value={value}
        />
    );

    expect(container.innerHTML).toMatchSnapshot();
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

    const {container} = render(
        <RoleAssignments
            disabled={true}
            onChange={jest.fn()}
            value={value}
        />
    );

    expect(container.innerHTML).toMatchSnapshot();
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
    const {instance: roleAssignments} = renderWithRef(
        <RoleAssignments
            onChange={onChangeSpy}
            value={value}
        />
    );

    findElementByType(roleAssignments.render(), ResourceMultiSelect).props.onChange(
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

    expect(onChangeSpy).toHaveBeenCalledWith(newValue);
});

test('Should trigger onChange correctly when RoleAssignment changes', () => {
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
    const {instance: roleAssignments} = renderWithRef(
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
    findAllElementsByType(roleAssignments.render(), RoleAssignment)[1].props.onChange(newValue[0]);
    expect(onChangeSpy).toHaveBeenCalledWith(newValue);
});
