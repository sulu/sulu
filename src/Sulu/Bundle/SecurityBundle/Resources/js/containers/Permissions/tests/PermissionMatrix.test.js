// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import PermissionMatrix from '../PermissionMatrix';
import type {ContextPermission} from '../types';
import type {SecurityContexts} from '../../../stores/securityContextStore/types';

jest.mock('sulu-admin-bundle/utils/Translator');

test('Render with minimal', () => {
    const contextPermissions: Array<ContextPermission> = [
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

    const securityContexts: SecurityContexts = {
        'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
        'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
    };

    const {container} = render(
        <PermissionMatrix
            contextPermissions={contextPermissions}
            onChange={jest.fn()}
            securityContexts={securityContexts}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render in disabled state', () => {
    const contextPermissions: Array<ContextPermission> = [
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

    const securityContexts: SecurityContexts = {
        'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
        'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
    };

    const {container} = render(
        <PermissionMatrix
            contextPermissions={contextPermissions}
            disabled={true}
            onChange={jest.fn()}
            securityContexts={securityContexts}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render with title', () => {
    const contextPermissions: Array<ContextPermission> = [
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

    const securityContexts: SecurityContexts = {
        'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
        'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
    };

    const {container} = render(
        <PermissionMatrix
            contextPermissions={contextPermissions}
            onChange={jest.fn()}
            securityContexts={securityContexts}
            title="Contact"
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render with subTitle', () => {
    const contextPermissions: Array<ContextPermission> = [
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

    const securityContexts: SecurityContexts = {
        'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
        'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
    };

    const {container} = render(
        <PermissionMatrix
            contextPermissions={contextPermissions}
            onChange={jest.fn()}
            securityContexts={securityContexts}
            subTitle="Contact"
        />
    );

    expect(container).toMatchSnapshot();
});

test('Should trigger onChange correctly', async() => {
    const user = userEvent.setup();
    const onChange = jest.fn();
    const contextPermissions: Array<ContextPermission> = [
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

    const securityContexts: SecurityContexts = {
        'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
        'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
    };

    render(
        <PermissionMatrix
            contextPermissions={contextPermissions}
            onChange={onChange}
            securityContexts={securityContexts}
        />
    );

    await user.click(screen.getAllByTitle('sulu_security.edit')[0]);

    const expectedContextPermissions: Array<ContextPermission> = [
        {
            id: 1,
            context: 'sulu.contact.people',
            permissions: {
                'view': true,
                'delete': true,
                'add': true,
                'edit': false,
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
    expect(onChange).toHaveBeenCalledWith(expectedContextPermissions);
});
