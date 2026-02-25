/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, waitFor} from '@testing-library/react';
import {webspaceStore} from 'sulu-page-bundle/stores';
import {defaultWebspace} from 'sulu-admin-bundle/utils/TestHelper';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import Permissions from '../Permissions';
import securityContextStore from '../../../stores/securityContextStore/securityContextStore';
import PermissionMatrix from '../PermissionMatrix';

jest.mock('sulu-page-bundle/stores/webspaceStore', () => ({
    allWebspaces: [],
}));

jest.mock('../../../stores/securityContextStore/securityContextStore', () => ({
    getSecurityContextGroups: jest.fn(() => Promise.resolve()),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../PermissionMatrix', () => jest.fn(() => <div data-testid="permission-matrix" />));

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');
    const Loader = jest.fn(() => <div data-testid="loader" />);
    const MultiSelect = jest.fn(({children}) => <div data-testid="multi-select">{children}</div>);
    function Option({children}) {
        return <>{children}</>;
    }
    MultiSelect.Option = Option;

    return {
        Loader,
        MultiSelect,
    };
});

const {MultiSelect} = jest.requireMock('sulu-admin-bundle/components');
const PermissionMatrixMock = PermissionMatrix;
const MultiSelectMock = MultiSelect;

const contactsContextGroups = {
    'Contacts': {
        'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
        'sulu.contact.organizations': ['view', 'add', 'edit', 'delete'],
    },
};

const webspaceContextGroups = {
    ...contactsContextGroups,
    'Webspaces': {
        'sulu.webspaces.#webspace#': ['view', 'add', 'edit', 'delete', 'live', 'security'],
        'sulu.webspaces.#webspace#.analytics': ['view', 'add', 'edit', 'delete'],
        'sulu.webspaces.#webspace#.default-snippets': ['view', 'add', 'edit', 'delete'],
    },
};

const contactsValue = [
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

const setWebspaces = (keys) => {
    webspaceStore.allWebspaces = keys.map((key) => ({
        ...defaultWebspace,
        key,
        name: key,
    }));
};

beforeEach(() => {
    jest.clearAllMocks();
    webspaceStore.allWebspaces = [];
});

test('renders minimal configuration', async() => {
    securityContextStore.getSecurityContextGroups.mockReturnValue(contactsContextGroups);

    const {asFragment} = render(
        <Permissions
            onChange={jest.fn()}
            system="Sulu"
            value={contactsValue}
        />
    );

    await waitFor(() => expect(PermissionMatrixMock).toHaveBeenCalled());
    expect(securityContextStore.getSecurityContextGroups).toHaveBeenCalledWith('Sulu');
    expect(asFragment()).toMatchSnapshot();
});

test('renders disabled state', async() => {
    securityContextStore.getSecurityContextGroups.mockReturnValue(contactsContextGroups);

    const {asFragment} = render(
        <Permissions
            disabled={true}
            onChange={jest.fn()}
            system="Sulu"
            value={contactsValue}
        />
    );

    await waitFor(() => expect(PermissionMatrixMock).toHaveBeenCalled());
    expect(getLatestMockProps(PermissionMatrixMock).disabled).toBe(true);
    expect(asFragment()).toMatchSnapshot();
});

test('triggers onChange when matrix changes', async() => {
    securityContextStore.getSecurityContextGroups.mockReturnValue(contactsContextGroups);
    const onChange = jest.fn();

    render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={contactsValue}
        />
    );

    await waitFor(() => expect(PermissionMatrixMock).toHaveBeenCalled());

    const newContextPermissions = [
        contactsValue[0],
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
    getLatestMockProps(PermissionMatrixMock).onChange(newContextPermissions);
    expect(onChange).toHaveBeenCalledWith(newContextPermissions);
});

test('renders with webspace section', async() => {
    securityContextStore.getSecurityContextGroups.mockReturnValue(webspaceContextGroups);
    setWebspaces(['example', 'example2', 'example3']);

    const {asFragment} = render(
        <Permissions
            onChange={jest.fn()}
            system="Sulu"
            value={contactsValue}
        />
    );

    await waitFor(() => expect(MultiSelectMock).toHaveBeenCalled());
    expect(getLatestMockProps(MultiSelectMock).values).toEqual([]);
    expect(asFragment()).toMatchSnapshot();
});

test('triggers onChange when changing webspace matrix section', async() => {
    securityContextStore.getSecurityContextGroups.mockReturnValue(webspaceContextGroups);
    setWebspaces(['example', 'example2', 'example3']);

    const value = [
        ...contactsValue,
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

    const onChange = jest.fn();

    render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={value}
        />
    );

    await waitFor(() => expect(PermissionMatrixMock).toHaveBeenCalledTimes(2));

    const newContextPermissions = [
        ...contactsValue,
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
    getLatestMockProps(PermissionMatrixMock).onChange(newContextPermissions);
    expect(onChange).toHaveBeenCalledWith(newContextPermissions);
});

test('triggers onChange when a webspace is added', async() => {
    securityContextStore.getSecurityContextGroups.mockReturnValue(webspaceContextGroups);
    setWebspaces(['example', 'example2', 'example3']);

    const onChange = jest.fn();
    render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={[
                ...contactsValue,
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
            ]}
        />
    );

    await waitFor(() => expect(MultiSelectMock).toHaveBeenCalled());
    getLatestMockProps(MultiSelectMock).onChange(['example', 'example3']);

    expect(onChange).toHaveBeenCalledWith([
        ...contactsValue,
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
    ]);
});

test('triggers onChange when a webspace is removed', async() => {
    securityContextStore.getSecurityContextGroups.mockReturnValue(webspaceContextGroups);
    setWebspaces(['example', 'example2', 'example3']);

    const onChange = jest.fn();
    render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={[
                ...contactsValue,
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
            ]}
        />
    );

    await waitFor(() => expect(MultiSelectMock).toHaveBeenCalled());
    getLatestMockProps(MultiSelectMock).onChange(['example3']);

    expect(onChange).toHaveBeenCalledWith([
        ...contactsValue,
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
    ]);
});

test('reloads security contexts when system changes', async() => {
    securityContextStore.getSecurityContextGroups.mockReturnValue(contactsContextGroups);

    const {rerender} = render(
        <Permissions
            onChange={jest.fn()}
            system="Sulu"
            value={contactsValue}
        />
    );

    await waitFor(() => expect(securityContextStore.getSecurityContextGroups).toHaveBeenCalledWith('Sulu'));
    rerender(
        <Permissions
            onChange={jest.fn()}
            system="Sulu"
            value={contactsValue}
        />
    );
    rerender(
        <Permissions
            onChange={jest.fn()}
            system="Other-System"
            value={contactsValue}
        />
    );

    await waitFor(() => expect(securityContextStore.getSecurityContextGroups).toHaveBeenCalledWith('Other-System'));
    expect(securityContextStore.getSecurityContextGroups).toHaveBeenCalledTimes(2);
});

test('passes disabled state to MultiSelect', async() => {
    securityContextStore.getSecurityContextGroups.mockReturnValue({
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view'],
        },
    });

    render(
        <Permissions
            disabled={true}
            onChange={jest.fn()}
            system="Sulu"
            value={[]}
        />
    );

    await waitFor(() => expect(MultiSelectMock).toHaveBeenCalled());
    expect(getLatestMockProps(MultiSelectMock).disabled).toBe(true);
});

test('disposes autorun on unmount', async() => {
    securityContextStore.getSecurityContextGroups.mockReturnValue(contactsContextGroups);
    const permissionsRef = React.createRef();

    const {unmount} = render(
        <Permissions
            onChange={jest.fn()}
            ref={permissionsRef}
            system="Sulu"
            value={contactsValue}
        />
    );

    await waitFor(() => expect(permissionsRef.current).toBeTruthy());
    const disposer = jest.fn();
    permissionsRef.current.systemDisposer = disposer;

    unmount();
    expect(disposer).toHaveBeenCalled();
});

test('restores original permission when webspace is removed and re-added', async() => {
    securityContextStore.getSecurityContextGroups.mockReturnValue({
        'Contacts': {
            'sulu.contact.people': ['view', 'add', 'edit', 'delete'],
        },
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view', 'add', 'edit', 'delete', 'live', 'security'],
            'sulu.webspaces.#webspace#.analytics': ['view', 'add', 'edit', 'delete'],
        },
    });
    setWebspaces(['example', 'example2']);

    const onChange = jest.fn();
    const value = [
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

    const {rerender} = render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={value}
        />
    );

    await waitFor(() => expect(MultiSelectMock).toHaveBeenCalled());

    getLatestMockProps(MultiSelectMock).onChange([]);
    const expectedAfterRemove = [
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

    rerender(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={expectedAfterRemove}
        />
    );

    await waitFor(() => expect(MultiSelectMock).toHaveBeenCalled());
    getLatestMockProps(MultiSelectMock).onChange(['example']);

    expect(onChange).toHaveBeenLastCalledWith([
        expectedAfterRemove[0],
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
    ]);
});

test('restores multiple webspaces independently', async() => {
    securityContextStore.getSecurityContextGroups.mockReturnValue({
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view', 'add', 'edit', 'delete', 'live', 'security'],
        },
    });
    setWebspaces(['example', 'example2']);

    const onChange = jest.fn();
    const value = [
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

    const {rerender} = render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={value}
        />
    );

    await waitFor(() => expect(MultiSelectMock).toHaveBeenCalled());
    getLatestMockProps(MultiSelectMock).onChange([]);
    expect(onChange).toHaveBeenLastCalledWith([]);

    rerender(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={[]}
        />
    );

    await waitFor(() => expect(MultiSelectMock).toHaveBeenCalled());
    getLatestMockProps(MultiSelectMock).onChange(['example2']);
    const expectedWithExample2 = [
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

    rerender(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={expectedWithExample2}
        />
    );

    await waitFor(() => expect(MultiSelectMock).toHaveBeenCalled());
    getLatestMockProps(MultiSelectMock).onChange(['example2', 'example']);
    expect(onChange).toHaveBeenLastCalledWith([
        expectedWithExample2[0],
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
    ]);
});

test('creates new permission for new webspace', async() => {
    securityContextStore.getSecurityContextGroups.mockReturnValue({
        'Webspaces': {
            'sulu.webspaces.#webspace#': ['view', 'add', 'edit', 'delete', 'live', 'security'],
        },
    });
    setWebspaces(['example', 'example2']);

    const onChange = jest.fn();
    render(
        <Permissions
            onChange={onChange}
            system="Sulu"
            value={[
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
            ]}
        />
    );

    await waitFor(() => expect(MultiSelectMock).toHaveBeenCalled());
    getLatestMockProps(MultiSelectMock).onChange(['example', 'example2']);

    expect(onChange).toHaveBeenLastCalledWith([
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
    ]);
});
