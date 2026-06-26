// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {MultiSelect} from 'sulu-admin-bundle/components';
import {findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import RoleAssignment from '../RoleAssignment';
import type {Localization} from 'sulu-admin-bundle/stores';

jest.mock('sulu-admin-bundle/utils/Translator');

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

    const {container} = render(
        <RoleAssignment
            localizations={localizations}
            onChange={jest.fn()}
            value={value}
        />
    );

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

    const {container} = render(
        <RoleAssignment
            disabled={true}
            localizations={localizations}
            onChange={jest.fn()}
            value={value}
        />
    );

    expect(container).toMatchSnapshot();
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
    const {instance: roleAssignment} = renderWithRef(
        <RoleAssignment
            localizations={localizations}
            onChange={onChangeSpy}
            value={value}
        />
    );

    findElementByType(roleAssignment.render(), MultiSelect).props.onChange(['de', 'en']);

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
