// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {autorun as mockAutorun} from 'mobx';
import {MultiSelect} from 'sulu-admin-bundle/components';
import {webspaceStore} from 'sulu-page-bundle/stores';
import {defaultWebspace} from 'sulu-admin-bundle/utils/TestHelper';
import Permissions from '../Permissions';
import securityContextStore from '../../../stores/securityContextStore/securityContextStore';
import PermissionMatrix from '../PermissionMatrix';
import type {ContextPermission} from '../types';
import type {SecurityContextGroups} from '../../../stores/securityContextStore/types';

jest.mock('sulu-page-bundle/stores/webspaceStore', () => ({
    allWebspaces: [],
}));

jest.mock('../../../stores/securityContextStore/securityContextStore', () => ({
    getSecurityContextGroups: jest.fn(() => Promise.resolve()),
}));

jest.mock('mobx', () => {
    const actualMobx = jest.requireActual('mobx');

    return {
        ...actualMobx,
        autorun: jest.fn((...args) => actualMobx.autorun(...args)),
    };
});

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');
    const actual = jest.requireActual('sulu-admin-bundle/components');
    const MultiSelect: any = jest.fn((props) => (
        <actual.MultiSelect {...props} />
    ));
    MultiSelect.Option = actual.MultiSelect.Option;

    return {
        ...actual,
        MultiSelect,
    };
});

jest.mock('../PermissionMatrix', () => {
    const React = require('react');
    const actual = jest.requireActual('../PermissionMatrix');

    return jest.fn((props) => (
        <actual.default {...props} />
    ));
});

const MultiSelectMock = (MultiSelect: any);
const PermissionMatrixMock = (PermissionMatrix: any);

const getMockCallProps = (mockComponent) => mockComponent.mock.calls.map(([props]) => props);

const getLastMockCallProps = (mockComponent) => {
    const props = getMockCallProps(mockComponent);
    if (props.length === 0) {
        throw new Error('Expected mock component to be called');
    }

    return props[props.length - 1];
};

const getPermissionMatrixPropsAt = (index) => {
    const props = getMockCallProps(PermissionMatrixMock);
    if (!props[index]) {
        throw new Error(`Expected PermissionMatrix to be called at index ${index}`);
    }

    return props[index];
};

const getMultiSelectProps = () => getLastMockCallProps(MultiSelectMock);

const renderPermissions = (customProps: Object) => {
    let props = {
        onChange: jest.fn(),
        system: 'Sulu',
        value: [],
        ...customProps,
    };

    const view = render(<Permissions {...props} />);

    return {
        ...view,
        rerenderWithProps: (newProps: Object) => {
            props = {...props, ...newProps};
            view.rerender(<Permissions {...props} />);
        },
    };
};

beforeEach(() => {
    const actualMobx = jest.requireActual('mobx');
    (mockAutorun: any).mockImplementation((...args) => actualMobx.autorun(...args));
    jest.clearAllMocks();
    webspaceStore.allWebspaces = [];
});

test('Render with minimal', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Contacts': {
            'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
            'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    const permissions = renderPermissions({
        onChange: jest.fn(),
        system: 'Sulu',
        value,
    });

    expect(securityContextStore.getSecurityContextGroups).toBeCalledWith('Sulu');
    expect(permissions.container.firstChild).toMatchSnapshot();
});

test('Render in disabled state', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Contacts': {
            'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
            'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    const permissions = renderPermissions({
        disabled: true,
        onChange: jest.fn(),
        system: 'Sulu',
        value,
    });

    expect(securityContextStore.getSecurityContextGroups).toBeCalledWith('Sulu');
    expect(permissions.container.firstChild).toMatchSnapshot();
});

test('Should trigger onChange correctly', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Contacts': {
            'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
            'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    const onChange = jest.fn();
    renderPermissions({
        onChange,
        system: 'Sulu',
        value,
    });

    const newContextPermissions: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': false,
            },
        },
    ];
    act(() => {
        getPermissionMatrixPropsAt(0).onChange(newContextPermissions);
    });
    expect(onChange).toBeCalledWith(newContextPermissions);
});

test('Render with empty webspace section', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Contacts': {
            'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
            'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
        },
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view', 'add', 'edit', 'delete', 'live', 'security'],
            'sulu.webspaces.#webspace#.analytics': ['view', 'add', 'edit', 'delete'],
            'sulu.webspaces.#webspace#.default-snippets': ['view', 'add', 'edit', 'delete'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    webspaceStore.allWebspaces = [
        {
            ...defaultWebspace,
            'key': 'example',
            'name': 'Example',
        },
        {
            ...defaultWebspace,
            'key': 'example2',
            'name': 'Example 2',
        },
        {
            ...defaultWebspace,
            'key': 'example3',
            'name': 'Example 3!',
        },
    ];

    const permissions = renderPermissions({
        onChange: jest.fn(),
        system: 'Sulu',
        value,
    });

    expect(securityContextStore.getSecurityContextGroups).toBeCalledWith('Sulu');

    const permissionChildren = permissions.container.children;
    expect(permissionChildren[0]).toMatchSnapshot();
    expect(permissionChildren[1]).toMatchSnapshot();
    expect(permissionChildren[2]).toMatchSnapshot();
    expect(permissionChildren[3]).toMatchSnapshot();
});

test('Render with webspace section', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 3,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Contacts': {
            'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
            'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
        },
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view', 'add', 'edit', 'delete', 'live', 'security'],
            'sulu.webspaces.#webspace#.analytics': ['view', 'add', 'edit', 'delete'],
            'sulu.webspaces.#webspace#.default-snippets': ['view', 'add', 'edit', 'delete'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    webspaceStore.allWebspaces = [
        {
            ...defaultWebspace,
            'key': 'example',
            'name': 'Example',
        },
        {
            ...defaultWebspace,
            'key': 'example2',
            'name': 'Example 2',
        },
        {
            ...defaultWebspace,
            'key': 'example3',
            'name': 'Example 3!',
        },
    ];

    const permissions = renderPermissions({
        onChange: jest.fn(),
        system: 'Sulu',
        value,
    });

    expect(securityContextStore.getSecurityContextGroups).toBeCalledWith('Sulu');
    const permissionChildren = permissions.container.children;
    expect(permissionChildren[0]).toMatchSnapshot();
    expect(permissionChildren[1]).toMatchSnapshot();
    expect(permissionChildren[2]).toMatchSnapshot();
    expect(permissionChildren[3]).toMatchSnapshot();
});

test('Should trigger onChange correctly when changing something in the webspace section', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 3,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Contacts': {
            'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
            'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
        },
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view', 'add', 'edit', 'delete', 'live', 'security'],
            'sulu.webspaces.#webspace#.analytics': ['view', 'add', 'edit', 'delete'],
            'sulu.webspaces.#webspace#.default-snippets': ['view', 'add', 'edit', 'delete'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    webspaceStore.allWebspaces = [
        {
            ...defaultWebspace,
            'key': 'example',
            'name': 'Example',
        },
        {
            ...defaultWebspace,
            'key': 'example2',
            'name': 'Example 2',
        },
        {
            ...defaultWebspace,
            'key': 'example3',
            'name': 'Example 3!',
        },
    ];

    const onChange = jest.fn();
    renderPermissions({
        onChange,
        system: 'Sulu',
        value,
    });

    const newContextPermissions: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 3,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': false,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
    ];
    act(() => {
        getPermissionMatrixPropsAt(0).onChange(newContextPermissions);
    });
    expect(onChange).toBeCalledWith(newContextPermissions);
});

test('Should trigger onChange correctly when a webspace is added', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 3,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Contacts': {
            'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
            'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
        },
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view', 'add', 'edit', 'delete', 'live', 'security'],
            'sulu.webspaces.#webspace#.analytics': ['view', 'add', 'edit', 'delete'],
            'sulu.webspaces.#webspace#.default-snippets': ['view', 'add', 'edit', 'delete'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    webspaceStore.allWebspaces = [
        {
            ...defaultWebspace,
            'key': 'example',
            'name': 'Example',
        },
        {
            ...defaultWebspace,
            'key': 'example2',
            'name': 'Example 2',
        },
        {
            ...defaultWebspace,
            'key': 'example3',
            'name': 'Example 3!',
        },
    ];

    const onChange = jest.fn();
    renderPermissions({
        onChange,
        system: 'Sulu',
        value,
    });

    act(() => {
        getMultiSelectProps().onChange(['example', 'example3']);
    });

    const expectedNewValue: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 3,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
        {
            id: undefined,
            context: 'sulu.webspaces.example3',
            permissions: {
                'view': false,
                'delete': false,
                'add': false,
                'edit': false,
                'live': false,
                'security': false,
            },
        },
        {
            id: undefined,
            context: 'sulu.webspaces.example3.analytics',
            permissions: {
                'view': false,
                'delete': false,
                'add': false,
                'edit': false,
            },
        },
        {
            id: undefined,
            context: 'sulu.webspaces.example3.default-snippets',
            permissions: {
                'view': false,
                'delete': false,
                'add': false,
                'edit': false,
            },
        },
    ];

    expect(onChange).toBeCalledWith(expectedNewValue);
});

test('Should trigger onChange correctly when a webspace is removed', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 3,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
        {
            id: 4,
            context: 'sulu.webspaces.example3',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': true,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Contacts': {
            'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
            'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
        },
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view', 'add', 'edit', 'delete', 'live', 'security'],
            'sulu.webspaces.#webspace#.analytics': ['view', 'add', 'edit', 'delete'],
            'sulu.webspaces.#webspace#.default-snippets': ['view', 'add', 'edit', 'delete'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    webspaceStore.allWebspaces = [
        {
            ...defaultWebspace,
            'key': 'example',
            'name': 'Example',
        },
        {
            ...defaultWebspace,
            'key': 'example2',
            'name': 'Example 2',
        },
        {
            ...defaultWebspace,
            'key': 'example3',
            'name': 'Example 3!',
        },
    ];

    const onChange = jest.fn();
    renderPermissions({
        onChange,
        system: 'Sulu',
        value,
    });

    act(() => {
        getMultiSelectProps().onChange(['example3']);
    });

    const expectedNewValue: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 4,
            context: 'sulu.webspaces.example3',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': true,
            },
        },
    ];

    expect(onChange).toBeCalledWith(expectedNewValue);
});

test('Should trigger a mobx autorun if the prop system changes', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Contacts': {
            'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
            'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    const permissions = renderPermissions({
        onChange: jest.fn(),
        system: 'Sulu',
        value,
    });

    // update with the same system, nothing should happen
    // update it with a other system it should trigger a reload
    permissions.rerenderWithProps({system: 'Sulu'});
    permissions.rerenderWithProps({system: 'Other-System'});

    expect(securityContextStore.getSecurityContextGroups).toHaveBeenCalledWith('Sulu');
    expect(securityContextStore.getSecurityContextGroups).toHaveBeenCalledWith('Other-System');
    expect(securityContextStore.getSecurityContextGroups).toHaveBeenCalledTimes(2);
});

test('Pass disabled state to MultiSelect', () => {
    const securityContextGroups: SecurityContextGroups = {
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    renderPermissions({
        disabled: true,
        onChange: jest.fn(),
        system: 'Sulu',
        value: [],
    });

    expect(getMultiSelectProps().disabled).toEqual(true);
});

test('Dispose autorun on unmount', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Contacts': {
            'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
            'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    const systemDisposerSpy = jest.fn();
    (mockAutorun: any).mockReturnValueOnce(systemDisposerSpy);

    const permissions = renderPermissions({
        onChange: jest.fn(),
        system: 'Sulu',
        value,
    });

    permissions.unmount();

    expect(systemDisposerSpy).toBeCalledWith();
});

test('Should restore original permission when webspace is removed and re-added without saving', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            id: 3,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
        {
            id: 5,
            context: 'sulu.webspaces.example.analytics',
            permissions: {
                'view': true,
                'delete': false,
                'add': true,
                'edit': false,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Contacts': {
            'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
        },
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view', 'add', 'edit', 'delete', 'live', 'security'],
            'sulu.webspaces.#webspace#.analytics': ['view', 'add', 'edit', 'delete'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    webspaceStore.allWebspaces = [
        {
            ...defaultWebspace,
            'key': 'example',
            'name': 'Example',
        },
        {
            ...defaultWebspace,
            'key': 'example2',
            'name': 'Example 2',
        },
    ];

    const onChange = jest.fn();
    const permissions = renderPermissions({
        onChange,
        system: 'Sulu',
        value,
    });

    // First remove the webspace
    act(() => {
        getMultiSelectProps().onChange([]);
    });

    const expectedAfterRemove: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
    ];

    expect(onChange).toHaveBeenLastCalledWith(expectedAfterRemove);

    // Update component props to reflect the removed state
    permissions.rerenderWithProps({value: expectedAfterRemove});

    // Now re-add the same webspace - it should restore the original permissions with their IDs
    act(() => {
        getMultiSelectProps().onChange(['example']);
    });

    const expectedAfterReAdd: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
            },
        },
        {
            // IMPORTANT: This should have the original id: 3, not id: undefined
            id: 3,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
        {
            // IMPORTANT: This should have the original id: 5, not id: undefined
            id: 5,
            context: 'sulu.webspaces.example.analytics',
            permissions: {
                'view': true,
                'delete': false,
                'add': true,
                'edit': false,
            },
        },
    ];

    expect(onChange).toHaveBeenLastCalledWith(expectedAfterReAdd);
});

test('Should restore multiple webspaces independently when removed and re-added', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
        {
            id: 2,
            context: 'sulu.webspaces.example2',
            permissions: {
                'view': false,
                'delete': true,
                'add': false,
                'edit': true,
                'live': false,
                'security': true,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view', 'add', 'edit', 'delete', 'live', 'security'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    webspaceStore.allWebspaces = [
        {
            ...defaultWebspace,
            'key': 'example',
            'name': 'Example',
        },
        {
            ...defaultWebspace,
            'key': 'example2',
            'name': 'Example 2',
        },
    ];

    const onChange = jest.fn();
    const permissions = renderPermissions({
        onChange,
        system: 'Sulu',
        value,
    });

    // Remove both webspaces
    act(() => {
        getMultiSelectProps().onChange([]);
    });
    expect(onChange).toHaveBeenLastCalledWith([]);

    // Update component props
    permissions.rerenderWithProps({value: []});

    // Re-add only example2 - should restore its original permissions
    act(() => {
        getMultiSelectProps().onChange(['example2']);
    });

    const expectedWithExample2: Array<ContextPermission> = [
        {
            id: 2,
            context: 'sulu.webspaces.example2',
            permissions: {
                'view': false,
                'delete': true,
                'add': false,
                'edit': true,
                'live': false,
                'security': true,
            },
        },
    ];

    expect(onChange).toHaveBeenLastCalledWith(expectedWithExample2);

    // Update component props again
    permissions.rerenderWithProps({value: expectedWithExample2});

    // Now also add example - should restore its original permissions
    act(() => {
        getMultiSelectProps().onChange(['example2', 'example']);
    });

    const expectedWithBoth: Array<ContextPermission> = [
        {
            id: 2,
            context: 'sulu.webspaces.example2',
            permissions: {
                'view': false,
                'delete': true,
                'add': false,
                'edit': true,
                'live': false,
                'security': true,
            },
        },
        {
            id: 1,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
    ];

    expect(onChange).toHaveBeenLastCalledWith(expectedWithBoth);
});

test('Should create new permission when adding a webspace that was never selected before', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view', 'add', 'edit', 'delete', 'live', 'security'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    webspaceStore.allWebspaces = [
        {
            ...defaultWebspace,
            'key': 'example',
            'name': 'Example',
        },
        {
            ...defaultWebspace,
            'key': 'example2',
            'name': 'Example 2',
        },
    ];

    const onChange = jest.fn();
    renderPermissions({
        onChange,
        system: 'Sulu',
        value,
    });

    // Add example2 which was never selected before - should create new permission with id: undefined
    act(() => {
        getMultiSelectProps().onChange(['example', 'example2']);
    });

    const expected: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
        {
            // Should be undefined because this webspace was never selected before
            id: undefined,
            context: 'sulu.webspaces.example2',
            permissions: {
                'view': false,
                'delete': false,
                'add': false,
                'edit': false,
                'live': false,
                'security': false,
            },
        },
    ];

    expect(onChange).toHaveBeenLastCalledWith(expected);
});

test('Should maintain removed permissions cache when toggling same webspace multiple times', () => {
    const value: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
    ];

    const securityContextGroups: SecurityContextGroups = {
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view', 'add', 'edit', 'delete', 'live', 'security'],
        },
    };
    securityContextStore.getSecurityContextGroups.mockReturnValue(securityContextGroups);

    webspaceStore.allWebspaces = [
        {
            ...defaultWebspace,
            'key': 'example',
            'name': 'Example',
        },
    ];

    const onChange = jest.fn();
    const permissions = renderPermissions({
        onChange,
        system: 'Sulu',
        value,
    });

    // Remove the webspace
    act(() => {
        getMultiSelectProps().onChange([]);
    });
    expect(onChange).toHaveBeenLastCalledWith([]);
    permissions.rerenderWithProps({value: []});

    // Re-add it - should restore original
    act(() => {
        getMultiSelectProps().onChange(['example']);
    });
    const firstReAdd = [
        {
            id: 1,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
    ];
    expect(onChange).toHaveBeenLastCalledWith(firstReAdd);
    permissions.rerenderWithProps({value: firstReAdd});

    // Remove it again
    act(() => {
        getMultiSelectProps().onChange([]);
    });
    expect(onChange).toHaveBeenLastCalledWith([]);
    permissions.rerenderWithProps({value: []});

    // Re-add it again - should still restore the original permission
    act(() => {
        getMultiSelectProps().onChange(['example']);
    });
    const secondReAdd = [
        {
            id: 1,
            context: 'sulu.webspaces.example',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': true,
                'live': false,
                'security': false,
            },
        },
    ];
    expect(onChange).toHaveBeenLastCalledWith(secondReAdd);
});
