// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import * as mobx from 'mobx';
import {webspaceStore} from 'sulu-page-bundle/stores';
import {defaultWebspace} from 'sulu-admin-bundle/utils/TestHelper';
import Permissions from '../Permissions';
import securityContextStore from '../../../stores/securityContextStore/securityContextStore';
import type {ContextPermission} from '../types';
import type {SecurityContextGroups} from '../../../stores/securityContextStore/types';

jest.mock('sulu-page-bundle/stores/webspaceStore', () => ({
    allWebspaces: [],
}));

jest.mock('../../../stores/securityContextStore/securityContextStore', () => ({
    getSecurityContextGroups: jest.fn(() => Promise.resolve()),
}));

jest.mock('sulu-admin-bundle/utils/Translator');

function getWebspaceSelectButton() {
    const button = document.querySelector('button.displayValue');

    if (!button) {
        throw new Error('Expected webspace select button');
    }

    return button;
}

async function toggleWebspace(user, name: string) {
    await user.click(getWebspaceSelectButton());
    await user.click(screen.getByRole('button', {name: new RegExp(name + '$')}));
    await user.click(screen.getByTestId('backdrop'));
}

function getPermissionButton(context: string, permission: string) {
    const contextCell = screen.getByText(context, {selector: 'td'});
    const row = contextCell.closest('tr');

    if (!row) {
        throw new Error('Expected permission row for ' + context);
    }

    return within(row).getByTitle('sulu_security.' + permission);
}

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

    const {container} = render(
        <Permissions
            onChange={jest.fn()}
            system="Sulu"
            value={value}
        />
    );

    expect(securityContextStore.getSecurityContextGroups).toHaveBeenCalledWith('Sulu');
    expect(container).toMatchSnapshot();
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

    const {container} = render(
        <Permissions
            disabled={true}
            onChange={jest.fn()}
            system="Sulu"
            value={value}
        />
    );

    expect(securityContextStore.getSecurityContextGroups).toHaveBeenCalledWith('Sulu');
    expect(container).toMatchSnapshot();
});

test('Should trigger onChange correctly', async() => {
    const user = userEvent.setup();
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
    render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={value}
        />
    );

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
    await user.click(getPermissionButton('organizations', 'edit'));
    expect(onChange).toHaveBeenCalledWith(newContextPermissions);
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

    const {container} = render(
        <Permissions
            onChange={jest.fn()}
            system="Sulu"
            value={value}
        />
    );

    expect(securityContextStore.getSecurityContextGroups).toHaveBeenCalledWith('Sulu');
    expect(container).toMatchSnapshot();
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

    const {container} = render(
        <Permissions
            onChange={jest.fn()}
            system="Sulu"
            value={value}
        />
    );

    expect(securityContextStore.getSecurityContextGroups).toHaveBeenCalledWith('Sulu');
    expect(container).toMatchSnapshot();
});

test('Should trigger onChange correctly when changing something in the webspace section', async() => {
    const user = userEvent.setup();
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
    render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={value}
        />
    );

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
        {
            id: undefined,
            context: 'sulu.webspaces.example.analytics',
            permissions: {},
        },
        {
            id: undefined,
            context: 'sulu.webspaces.example.default-snippets',
            permissions: {},
        },
    ];
    await user.click(getPermissionButton('example', 'add'));
    expect(onChange).toHaveBeenCalledWith(newContextPermissions);
});

test('Should trigger onChange correctly when a webspace is added', async() => {
    const user = userEvent.setup();
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
    render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={value}
        />
    );

    await toggleWebspace(user, 'Example 3!');

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

    expect(onChange).toHaveBeenCalledWith(expectedNewValue);
});

test('Should trigger onChange correctly when a webspace is removed', async() => {
    const user = userEvent.setup();
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
    render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={value}
        />
    );

    await toggleWebspace(user, 'Example');

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

    expect(onChange).toHaveBeenCalledWith(expectedNewValue);
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

    const {rerender} = render(
        <Permissions
            onChange={jest.fn()}
            system="Sulu"
            value={value}
        />
    );

    // update with the same system, nothing should happen
    // update it with a other system it should trigger a reload
    rerender(
        <Permissions
            onChange={jest.fn()}
            system="Sulu"
            value={value}
        />
    );
    rerender(
        <Permissions
            onChange={jest.fn()}
            system="Other-System"
            value={value}
        />
    );

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

    render(
        <Permissions
            disabled={true}
            onChange={jest.fn()}
            system="Sulu"
            value={[]}
        />
    );

    expect(getWebspaceSelectButton()).toBeDisabled();
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
    const autorunSpy = jest.spyOn(mobx, 'autorun').mockImplementation((view: () => void) => {
        view();

        return systemDisposerSpy;
    });
    const {unmount} = render(
        <Permissions
            onChange={jest.fn()}
            system="Sulu"
            value={value}
        />
    );

    unmount();

    expect(systemDisposerSpy).toHaveBeenCalledWith();
    autorunSpy.mockRestore();
});

test('Should restore original permission when webspace is removed and re-added without saving', async() => {
    const user = userEvent.setup();
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
    const {rerender} = render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={value}
        />
    );

    // First remove the webspace
    await toggleWebspace(user, 'Example');

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
    rerender(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={expectedAfterRemove}
        />
    );

    // Now re-add the same webspace - it should restore the original permissions with their IDs
    await toggleWebspace(user, 'Example');

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

test('Should restore multiple webspaces independently when removed and re-added', async() => {
    const user = userEvent.setup();
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
    const {rerender} = render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={value}
        />
    );

    // Remove both webspaces through the controlled select.
    await toggleWebspace(user, 'Example');
    const expectedAfterFirstRemove = [value[1]];
    expect(onChange).toHaveBeenLastCalledWith(expectedAfterFirstRemove);
    rerender(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={expectedAfterFirstRemove}
        />
    );
    await toggleWebspace(user, 'Example 2');
    expect(onChange).toHaveBeenLastCalledWith([]);

    // Update component props
    rerender(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={[]}
        />
    );

    // Re-add only example2 - should restore its original permissions
    await toggleWebspace(user, 'Example 2');

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
    rerender(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={expectedWithExample2}
        />
    );

    // Now also add example - should restore its original permissions
    await toggleWebspace(user, 'Example');

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

test('Should create new permission when adding a webspace that was never selected before', async() => {
    const user = userEvent.setup();
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
    render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={value}
        />
    );

    // Add example2 which was never selected before - should create new permission with id: undefined
    await toggleWebspace(user, 'Example 2');

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

test('Should maintain removed permissions cache when toggling same webspace multiple times', async() => {
    const user = userEvent.setup();
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
    const {rerender} = render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={value}
        />
    );

    // Remove the webspace
    await toggleWebspace(user, 'Example');
    expect(onChange).toHaveBeenLastCalledWith([]);
    rerender(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={[]}
        />
    );

    // Re-add it - should restore original
    await toggleWebspace(user, 'Example');
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
    rerender(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={firstReAdd}
        />
    );

    // Remove it again
    await toggleWebspace(user, 'Example');
    expect(onChange).toHaveBeenLastCalledWith([]);
    rerender(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={[]}
        />
    );

    // Re-add it again - should still restore the original permission
    await toggleWebspace(user, 'Example');
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
