// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {findMockCallArg} from 'sulu-admin-bundle/utils/TestHelper';
import securityContextStore from '../../../stores/securityContextStore';
import RolePermissions from '../RolePermissions';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn(),
}));

jest.mock('../SystemRolePermissions', () => jest.fn(() => null));

jest.mock('../../../stores/securityContextStore', () => ({
    suluSecuritySystem: 'Sulu',
    resourceKeyMapping: {snippets: 'sulu.global.snippets'},
    getAvailableActions: jest.fn(),
    getSecurityContextByResourceKey: jest.fn(),
    getSystems: jest.fn(),
}));

const SystemRolePermissionsMock: any = jest.requireMock('../SystemRolePermissions');

function getSystemRolePermissionsProps(system: string): any {
    return findMockCallArg(SystemRolePermissionsMock, ([props]) => props.system === system);
}

async function waitForRoles(rolePromise: Promise<*>): Promise<void> {
    await act(async() => {
        await rolePromise;
    });
}

RolePermissions.suluSecuritySystem = 'Sulu';

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render matrix with correct given values', async() => {
    const rolePromise = Promise.resolve({
        _embedded: {
            roles: [
                {id: 1, name: 'Admin', system: 'Sulu'},
                {id: 2, name: 'Contact Manager', system: 'Sulu'},
            ],
        },
    });
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'security']);
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website']);

    const value = {
        '1': {view: true, add: false, edit: true, delete: true},
        '2': {view: true, add: true, edit: true, delete: false},
    };

    const {asFragment} = render(<RolePermissions onChange={jest.fn()} resourceKey="snippets" value={value} />);
    expect(asFragment()).toMatchSnapshot();

    await waitForRoles(rolePromise);
    expect(asFragment()).toMatchSnapshot();

    expect(getSystemRolePermissionsProps('Sulu').values).toEqual(value);
});

test('Hide system if specific system is given', async() => {
    const rolePromise = Promise.resolve({
        _embedded: {
            roles: [
                {id: 1, name: 'Admin', system: 'Sulu'},
                {id: 2, name: 'Contact Manager', system: 'Website'},
                {id: 3, name: 'Blog Manager', system: 'Blog'},
            ],
        },
    });
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'security']);
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website', 'Blog']);

    render(
        <RolePermissions onChange={jest.fn()} permissionCheck={true} resourceKey="snippets" system="Blog" value={{}} />
    );
    await waitForRoles(rolePromise);

    expect(SystemRolePermissionsMock).toHaveBeenCalledTimes(2);
    expect(getSystemRolePermissionsProps('Sulu').system).toEqual('Sulu');
    expect(getSystemRolePermissionsProps('Blog').system).toEqual('Blog');
});

test(
    'Show only Sulu system if specific system is given and permissionCheck is set to false for that system',
    async() => {
        const rolePromise = Promise.resolve({
            _embedded: {
                roles: [
                    {id: 1, name: 'Admin', system: 'Sulu'},
                    {id: 2, name: 'Contact Manager', system: 'Website'},
                    {id: 3, name: 'Blog Manager', system: 'Blog'},
                ],
            },
        });
        ResourceRequester.get.mockReturnValue(rolePromise);

        securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'security']);
        securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website', 'Blog']);

        render(
            <RolePermissions
                onChange={jest.fn()}
                permissionCheck={false}
                resourceKey="snippets"
                system="Blog"
                value={{}}
            />
        );
        await waitForRoles(rolePromise);

        expect(SystemRolePermissionsMock).toHaveBeenCalledTimes(1);
        expect(getSystemRolePermissionsProps('Sulu').system).toEqual('Sulu');
    }
);

test('Hide system if no actions are given', async() => {
    const rolePromise = Promise.resolve({
        _embedded: {
            roles: [
                {id: 1, name: 'Admin', system: 'Sulu'},
                {id: 2, name: 'Contact Manager', system: 'Website'},
            ],
        },
    });
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockImplementation((resourceKey, system) => {
        if (system === 'Sulu') {
            return ['view', 'add', 'edit', 'delete', 'security'];
        }

        if (system === 'Website') {
            return [];
        }

        return [];
    });
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website']);

    render(<RolePermissions onChange={jest.fn()} resourceKey="snippets" value={{}} />);
    await waitForRoles(rolePromise);

    expect(SystemRolePermissionsMock).toHaveBeenCalledTimes(1);
    expect(getSystemRolePermissionsProps('Sulu').system).toEqual('Sulu');
});

test('Call onChange callback when value changes', async() => {
    const changeSpy = jest.fn();

    const rolePromise = Promise.resolve({
        _embedded: {
            roles: [
                {id: 1, name: 'Administrator', permissions: [], system: 'Sulu'},
                {id: 2, name: 'Account Manager', permissions: [], system: 'Sulu'},
            ],
        },
    });
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'live', 'security']);
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website']);

    const value = {'1': {view: true, add: true, edit: true, delete: true}};

    render(<RolePermissions onChange={changeSpy} resourceKey="snippets" value={value} />);
    await waitForRoles(rolePromise);

    expect(securityContextStore.getAvailableActions).toBeCalledWith('snippets', 'Sulu');
    expect(securityContextStore.getAvailableActions).toBeCalledWith('snippets', 'Website');

    act(() => {
        getSystemRolePermissionsProps('Sulu').onChange({
            '2': {
                view: true,
                add: true,
                edit: true,
                delete: false,
            },
        }, 'Sulu');
    });

    expect(changeSpy).toHaveBeenLastCalledWith({
        '2': {
            view: true,
            add: true,
            edit: true,
            delete: false,
        },
    });
});

test('Call onChange callback when matrix for system is deactivated', async() => {
    const changeSpy = jest.fn();

    const rolePromise = Promise.resolve({
        _embedded: {
            roles: [
                {id: 1, name: 'Website User', permissions: [], system: 'Website'},
                {id: 2, name: 'Account Manager', permissions: [], system: 'Sulu'},
                {id: 3, name: 'Website Manager', permissions: [], system: 'Website'},
                {id: 4, name: 'Administrator', permissions: [], system: 'Sulu'},
            ],
        },
    });
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'live', 'security']);
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website']);

    const value = {
        '1': {view: true, add: true, edit: true, delete: true},
        '2': {view: true, add: true, edit: true, delete: true},
        '3': {view: true, add: true, edit: true, delete: false},
        '4': {view: true, add: true, edit: true, delete: false},
    };

    render(<RolePermissions onChange={changeSpy} resourceKey="snippets" value={value} />);
    await waitForRoles(rolePromise);

    act(() => {
        getSystemRolePermissionsProps('Sulu').onChange({}, 'Sulu');
    });

    expect(changeSpy).toHaveBeenLastCalledWith({
        '1': {view: true, add: true, edit: true, delete: true},
        '3': {view: true, add: true, edit: true, delete: false},
    });
});

test('Call onChange callback when new matrix for system is added', async() => {
    const changeSpy = jest.fn();

    const rolePromise = Promise.resolve({
        _embedded: {
            roles: [
                {id: 1, name: 'Website User', permissions: [], system: 'Website'},
                {id: 2, name: 'Account Manager', permissions: [], system: 'Sulu'},
                {id: 3, name: 'Website Manager', permissions: [], system: 'Website'},
                {id: 4, name: 'Administrator', permissions: [], system: 'Sulu'},
            ],
        },
    });
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'live']);
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website']);

    const value = {
        '1': {view: true, add: true, edit: true, delete: true},
        '3': {view: true, add: true, edit: true, delete: false},
    };

    render(<RolePermissions onChange={changeSpy} resourceKey="snippets" value={value} />);
    await waitForRoles(rolePromise);

    act(() => {
        getSystemRolePermissionsProps('Sulu').onChange({
            '2': {
                view: true,
                add: false,
                edit: false,
                delete: false,
                live: false,
            },
            '4': {
                view: false,
                add: false,
                edit: false,
                delete: false,
                live: false,
            },
        }, 'Sulu');
    });

    expect(changeSpy).toHaveBeenLastCalledWith({
        '1': {
            view: true,
            add: true,
            edit: true,
            delete: true,
        },
        '2': {
            view: true,
            add: false,
            edit: false,
            delete: false,
            live: false,
        },
        '3': {
            view: true,
            add: true,
            edit: true,
            delete: false,
        },
        '4': {
            view: false,
            add: false,
            edit: false,
            delete: false,
            live: false,
        },
    });
});

test('Pass webspaceKey through to SystemRolePermissions', async() => {
    const rolePromise = Promise.resolve({
        _embedded: {
            roles: [
                {id: 1, name: 'Administrator', permissions: [], system: 'Sulu'},
            ],
        },
    });
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add']);
    securityContextStore.getSystems.mockReturnValue(['Sulu']);

    render(<RolePermissions onChange={jest.fn()} resourceKey="pages" value={{}} webspaceKey="website" />);
    await waitForRoles(rolePromise);

    expect(getSystemRolePermissionsProps('Sulu').webspaceKey).toEqual('website');
});
