// @flow
import React from 'react';
import {mount} from 'enzyme';
import {localizationStore} from 'sulu-admin-bundle/stores';
import type {Localization} from 'sulu-admin-bundle/stores';
import {MultiSelect} from 'sulu-admin-bundle/containers';
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
        loadLocalizations: jest.fn(),
    },
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

test('Render component with min', () => {
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
    const promise = Promise.resolve(localizations);
    localizationStore.loadLocalizations.mockReturnValueOnce(promise);

    const roleAssignments = mount(
        <RoleAssignments
            onChange={jest.fn()}
            value={[]}
        />
    );

    return promise.then(() => {
        expect(localizationStore.loadLocalizations).toBeCalled();
        roleAssignments.update();
        expect(roleAssignments.instance().localizations).toEqual(localizations);
        expect(roleAssignments.render()).toMatchSnapshot();
    });
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
    const promise = Promise.resolve(localizations);
    localizationStore.loadLocalizations.mockReturnValueOnce(promise);

    const roleAssignments = mount(
        <RoleAssignments
            onChange={jest.fn()}
            value={value}
        />
    );

    return promise.then(() => {
        expect(localizationStore.loadLocalizations).toBeCalled();
        roleAssignments.update();
        expect(roleAssignments.render()).toMatchSnapshot();
    });
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
    const promise = Promise.resolve(localizations);
    localizationStore.loadLocalizations.mockReturnValueOnce(promise);

    const onChangeSpy = jest.fn();
    const roleAssignments = mount(
        <RoleAssignments
            onChange={onChangeSpy}
            value={value}
        />
    );

    return promise.then(() => {
        roleAssignments.update();
        roleAssignments.find(MultiSelect).at(0).instance().props.onChange(
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
    const promise = Promise.resolve(localizations);
    localizationStore.loadLocalizations.mockReturnValueOnce(promise);

    const onChangeSpy = jest.fn();
    const roleAssignments = mount(
        <RoleAssignments
            onChange={onChangeSpy}
            value={value}
        />
    );

    return promise.then(() => {
        roleAssignments.update();
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
        roleAssignments.find(RoleAssignment).at(1).instance().props.onChange(newValue[0]);
        expect(onChangeSpy).toBeCalledWith(newValue);
    });
});
