// @flow
import React from 'react';
import {mount} from 'enzyme';
import ResourceListStore from 'sulu-admin-bundle/stores/ResourceListStore';
import Permissions from '../Permissions';
import type {ContextPermission} from '../types';
import type {SecurityContextGroups} from '../../../stores/SecurityContextsStore/types';
import securityContextsStore from '../../../stores/SecurityContextsStore/SecurityContextsStore';

jest.mock('sulu-admin-bundle/stores/ResourceListStore', () => jest.fn());

jest.mock('../../../stores/SecurityContextsStore/SecurityContextsStore', () => ({
    loadSecurityContextGroups: jest.fn(() => Promise.resolve()),
}));

jest.mock('sulu-admin-bundle/utils', () => ({
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
                'name': 'Example 3',
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
        expect(permissions.render()).toMatchSnapshot();
    });
});
