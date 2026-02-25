// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {MultiSelect} from 'sulu-admin-bundle/components';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import RoleAssignment from '../RoleAssignment';
import type {Localization} from 'sulu-admin-bundle/stores';

jest.mock('mobx-react', () => ({
    observer: (Component) => Component,
}));

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');
    const MultiSelect: any = jest.fn(({children}) => <div data-testid="multi-select">{children}</div>);

    MultiSelect.Option = jest.fn(({children}) => <div data-testid="multi-select-option">{children}</div>);

    return {
        MultiSelect,
    };
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

const MultiSelectMock: any = MultiSelect;

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

beforeEach(() => {
    MultiSelectMock.mockClear();
});

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

test('The component should trigger the change callback', () => {
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

    const multiSelectProps = getLatestMockProps(MultiSelectMock);
    multiSelectProps.onChange(['de', 'en']);

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
