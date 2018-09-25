// @flow
import React from 'react';
import {mount} from 'enzyme';
import ResourceListStore from 'sulu-admin-bundle/stores/ResourceListStore';
import {MultiSelect} from 'sulu-admin-bundle/containers';
import Permissions from '../Permissions';
import type {ContextPermission} from '../types';
import type {SecurityContextGroups} from '../../../stores/SecurityContextsStore/types';
import securityContextsStore from '../../../stores/SecurityContextsStore/SecurityContextsStore';
import PermissionMatrix from '../PermissionMatrix';

jest.mock('sulu-admin-bundle/stores/ResourceListStore', () => jest.fn());

jest.mock('../../../stores/SecurityContextsStore/SecurityContextsStore', () => ({
    loadSecurityContextGroups: jest.fn(() => Promise.resolve()),
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

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
    const promise = Promise.resolve(securityContextGroups);
    securityContextsStore.loadSecurityContextGroups.mockReturnValue(promise);

    const permissions = mount(
        <Permissions
            onChange={jest.fn()}
            system={'Sulu'}
            value={value}
        />
    );

    return promise.then(() => {
        expect(securityContextsStore.loadSecurityContextGroups).toBeCalledWith('Sulu');
        permissions.update();
        expect(permissions.render()).toMatchSnapshot();
    });
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
    const promise = Promise.resolve(securityContextGroups);
    securityContextsStore.loadSecurityContextGroups.mockReturnValue(promise);

    const onChange = jest.fn();
    const permissions = mount(
        <Permissions
            onChange={onChange}
            system={'Sulu'}
            value={value}
        />
    );

    return promise.then(() => {
        permissions.update();
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
        permissions.find(PermissionMatrix).at(0).instance().props.onChange(newContextPermissions);
        expect(onChange).toBeCalledWith(newContextPermissions);
    });
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
    const promise = Promise.resolve(securityContextGroups);
    securityContextsStore.loadSecurityContextGroups.mockReturnValue(promise);

    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                'key': 'example',
                'name': 'Example',
            },
            {
                'key': 'example2',
                'name': 'Example 2',
            },
            {
                'key': 'example3',
                'name': 'Example 3!',
            },
        ];
    });

    const permissions = mount(
        <Permissions
            onChange={jest.fn()}
            system={'Sulu'}
            value={value}
        />
    );

    return promise.then(() => {
        expect(securityContextsStore.loadSecurityContextGroups).toBeCalledWith('Sulu');
        permissions.update();

        // Currently we have to load each child separately, because of a bug in enzyme.
        // Fixme: https://github.com/airbnb/enzyme/issues/1213
        const permissionChildren = permissions.children();
        expect(permissionChildren.at(0).render()).toMatchSnapshot();
        expect(permissionChildren.at(1).render()).toMatchSnapshot();
        expect(permissionChildren.at(2).render()).toMatchSnapshot();
        expect(permissionChildren.at(3).render()).toMatchSnapshot();
    });
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
    const promise = Promise.resolve(securityContextGroups);
    securityContextsStore.loadSecurityContextGroups.mockReturnValue(promise);

    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                'key': 'example',
                'name': 'Example',
            },
            {
                'key': 'example2',
                'name': 'Example 2',
            },
            {
                'key': 'example3',
                'name': 'Example 3!',
            },
        ];
    });

    const permissions = mount(
        <Permissions
            onChange={jest.fn()}
            system={'Sulu'}
            value={value}
        />
    );

    return promise.then(() => {
        expect(securityContextsStore.loadSecurityContextGroups).toBeCalledWith('Sulu');
        permissions.update();

        // Currently we have to load each child separately, because of a bug in enzyme.
        // Fixme: https://github.com/airbnb/enzyme/issues/1213
        const permissionChildren = permissions.children();
        expect(permissionChildren.at(0).render()).toMatchSnapshot();
        expect(permissionChildren.at(1).render()).toMatchSnapshot();
        expect(permissionChildren.at(2).render()).toMatchSnapshot();
        expect(permissionChildren.at(3).render()).toMatchSnapshot();
    });
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
    const promise = Promise.resolve(securityContextGroups);
    securityContextsStore.loadSecurityContextGroups.mockReturnValue(promise);

    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                'key': 'example',
                'name': 'Example',
            },
            {
                'key': 'example2',
                'name': 'Example 2',
            },
            {
                'key': 'example3',
                'name': 'Example 3!',
            },
        ];
    });

    const onChange = jest.fn();
    const permissions = mount(
        <Permissions
            onChange={onChange}
            system={'Sulu'}
            value={value}
        />
    );

    return promise.then(() => {
        permissions.update();
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
        permissions.find(PermissionMatrix).at(0).instance().props.onChange(newContextPermissions);
        expect(onChange).toBeCalledWith(newContextPermissions);
    });
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
    const promise = Promise.resolve(securityContextGroups);
    securityContextsStore.loadSecurityContextGroups.mockReturnValue(promise);

    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                'key': 'example',
                'name': 'Example',
            },
            {
                'key': 'example2',
                'name': 'Example 2',
            },
            {
                'key': 'example3',
                'name': 'Example 3!',
            },
        ];
    });

    const onChange = jest.fn();
    const permissions = mount(
        <Permissions
            onChange={onChange}
            system={'Sulu'}
            value={value}
        />
    );

    return promise.then(() => {
        permissions.update();
        permissions.find(MultiSelect).at(0).instance().props.onChange(['example', 'example3']);

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
    const promise = Promise.resolve(securityContextGroups);
    securityContextsStore.loadSecurityContextGroups.mockReturnValue(promise);

    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                'key': 'example',
                'name': 'Example',
            },
            {
                'key': 'example2',
                'name': 'Example 2',
            },
            {
                'key': 'example3',
                'name': 'Example 3!',
            },
        ];
    });

    const onChange = jest.fn();
    const permissions = mount(
        <Permissions
            onChange={onChange}
            system={'Sulu'}
            value={value}
        />
    );

    return promise.then(() => {
        permissions.update();
        permissions.find(MultiSelect).at(0).instance().props.onChange(['example3']);

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
});
