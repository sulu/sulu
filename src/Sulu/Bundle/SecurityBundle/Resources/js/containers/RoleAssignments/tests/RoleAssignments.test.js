// @flow
import {render} from '@testing-library/react';
import React from 'react';
import {localizationStore} from 'sulu-admin-bundle/stores';
import {ResourceMultiSelect} from 'sulu-admin-bundle/containers';
import RoleAssignments from '../RoleAssignments';
import RoleAssignment from '../RoleAssignment';

jest.mock('sulu-admin-bundle/containers', () => ({
    ResourceMultiSelect: jest.fn(() => null),
}));

jest.mock('../RoleAssignment', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/stores', () => ({
    localizationStore: {
        localizations: undefined,
    },
}));

beforeEach(() => {
    jest.clearAllMocks();
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
});

function createValue() {
    return [
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
}

test('Render component without data', () => {
    render(
        <RoleAssignments
            onChange={jest.fn()}
            value={[]}
        />
    );

    const [resourceMultiSelectProps] = (ResourceMultiSelect: any).mock.calls[0];
    expect(resourceMultiSelectProps.values).toEqual([]);
    expect(RoleAssignment).not.toHaveBeenCalled();
});

test('Render component', () => {
    const value = createValue();

    render(
        <RoleAssignments
            onChange={jest.fn()}
            value={value}
        />
    );

    const [resourceMultiSelectProps] = (ResourceMultiSelect: any).mock.calls[0];
    expect(resourceMultiSelectProps.values).toEqual([23, 5]);
    expect(RoleAssignment).toHaveBeenCalledTimes(2);
});

test('Render component in disabled state', () => {
    const value = createValue();

    render(
        <RoleAssignments
            disabled={true}
            onChange={jest.fn()}
            value={value}
        />
    );

    const [resourceMultiSelectProps] = (ResourceMultiSelect: any).mock.calls[0];
    expect(resourceMultiSelectProps.disabled).toEqual(true);

    const [roleAssignmentProps] = (RoleAssignment: any).mock.calls[0];
    expect(roleAssignmentProps.disabled).toEqual(true);
});

test('Should trigger onChange correctly when MultiSelect for roles changes', () => {
    const value = createValue();
    const onChangeSpy = jest.fn();

    render(
        <RoleAssignments
            onChange={onChangeSpy}
            value={value}
        />
    );

    const [resourceMultiSelectProps] = (ResourceMultiSelect: any).mock.calls[0];
    resourceMultiSelectProps.onChange(
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

    expect(onChangeSpy).toBeCalledWith([
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
    ]);
});

test('Should trigger onChange correctly when RoleAssignment changes', () => {
    const value = createValue();
    const onChangeSpy = jest.fn();

    render(
        <RoleAssignments
            onChange={onChangeSpy}
            value={value}
        />
    );

    const newRoleAssignment = {
        id: 1,
        role: {
            id: 5,
            name: 'Role Name 5',
            system: 'Sulu',
        },
        locales: ['de'],
    };

    const [, secondRoleAssignmentProps] = (RoleAssignment: any).mock.calls;
    secondRoleAssignmentProps[0].onChange(newRoleAssignment);

    expect(onChangeSpy).toBeCalledWith([
        newRoleAssignment,
        {
            id: 2,
            role: {
                id: 23,
                name: 'Role Name 23',
                system: 'Sulu',
            },
            locales: ['de'],
        },
    ]);
});
