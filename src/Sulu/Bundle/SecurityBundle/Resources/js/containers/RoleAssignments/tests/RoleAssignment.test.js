// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import type {Localization} from 'sulu-admin-bundle/stores';
import {MultiSelect} from 'sulu-admin-bundle/components';
import RoleAssignment from '../RoleAssignment';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

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

    expect(render(
        <RoleAssignment
            localizations={localizations}
            onChange={jest.fn()}
            value={value}
        />
    )).toMatchSnapshot();
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

    expect(render(
        <RoleAssignment
            disabled={true}
            localizations={localizations}
            onChange={jest.fn()}
            value={value}
        />
    )).toMatchSnapshot();
});

test('The component should trigger the change callback', () => {
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
    const roleAssignment = mount(
        <RoleAssignment
            localizations={localizations}
            onChange={onChangeSpy}
            value={value}
        />
    );

    roleAssignment.find(MultiSelect).props().onChange(['de', 'en']);

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
