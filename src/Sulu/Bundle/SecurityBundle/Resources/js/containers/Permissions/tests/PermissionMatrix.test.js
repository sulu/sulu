// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import PermissionMatrix from '../PermissionMatrix';
import type {ContextPermission} from '../types';
import type {SecurityContexts} from '../../../stores/securityContextStore/types';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

const contextPermissions: Array<ContextPermission> = [
    {
        id: 1,
        context: 'sulu.contact.people',
        permissions: {
            view: true,
            delete: true,
            add: true,
            edit: true,
        },
    },
    {
        id: 2,
        context: 'sulu.contact.organizations',
        permissions: {
            view: true,
            delete: true,
            add: true,
            edit: true,
        },
    },
];

const securityContexts: SecurityContexts = {
    'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
    'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
};

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render with minimal', () => {
    const {asFragment} = render(
        <PermissionMatrix
            contextPermissions={contextPermissions}
            onChange={jest.fn()}
            securityContexts={securityContexts}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render in disabled state', () => {
    const {asFragment} = render(
        <PermissionMatrix
            contextPermissions={contextPermissions}
            disabled={true}
            onChange={jest.fn()}
            securityContexts={securityContexts}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render with title', () => {
    const {asFragment} = render(
        <PermissionMatrix
            contextPermissions={contextPermissions}
            onChange={jest.fn()}
            securityContexts={securityContexts}
            title="Contact"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render with subTitle', () => {
    const {asFragment} = render(
        <PermissionMatrix
            contextPermissions={contextPermissions}
            onChange={jest.fn()}
            securityContexts={securityContexts}
            subTitle="Contact"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should trigger onChange correctly', async() => {
    const onChange = jest.fn();
    const user = userEvent.setup();

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
                view: true,
                delete: true,
                add: true,
                edit: false,
            },
        },
        {
            id: 2,
            context: 'sulu.contact.organizations',
            permissions: {
                view: true,
                delete: true,
                add: true,
                edit: true,
            },
        },
    ];
    expect(onChange).toBeCalledWith(expectedContextPermissions);
});
