// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import type {MatrixValues} from 'sulu-admin-bundle/components/Matrix/types';
import PermissionMatrix from '../PermissionMatrix';
import type {ContextPermission} from '../types';
import type {SecurityContexts} from '../../../stores/SecurityContextStore/types';

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: (key) => key,
}));

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

    expect(render(
        <PermissionMatrix
            contextPermissions={contextPermissions}
            onChange={jest.fn()}
            securityContexts={securityContexts}
        />
    )).toMatchSnapshot();
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

    expect(render(
        <PermissionMatrix
            contextPermissions={contextPermissions}
            onChange={jest.fn()}
            securityContexts={securityContexts}
            title={'Contact'}
        />
    )).toMatchSnapshot();
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

    expect(render(
        <PermissionMatrix
            contextPermissions={contextPermissions}
            onChange={jest.fn()}
            securityContexts={securityContexts}
            subTitle={'Contact'}
        />
    )).toMatchSnapshot();
});

test('Should trigger onChange correctly', () => {
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

    const permissionMatrix = mount(
        <PermissionMatrix
            contextPermissions={contextPermissions}
            onChange={onChange}
            securityContexts={securityContexts}
        />
    );

    const matrixValues: MatrixValues = {
        'sulu.contact.people': {
            'view': true,
            'delete': true,
            'add': true,
            'edit': false,
        },
    };
    permissionMatrix.find('Matrix').instance().props.onChange(matrixValues);

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
    expect(onChange).toBeCalledWith(expectedContextPermissions);
});
