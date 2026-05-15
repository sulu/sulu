// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {Heading, Matrix, Toggler} from 'sulu-admin-bundle/components';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import securityContextStore from '../../../stores/securityContextStore';
import RolePermissions from '../RolePermissions';

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');

    const Loader = jest.fn(() => <div>loader</div>);
    const Heading = jest.fn(({label, children}) => (
        <div>
            <span>{label}</span>
            {children}
        </div>
    ));
    const Toggler = jest.fn(() => null);
    const Matrix = jest.fn(({children}) => <div>{children}</div>);
    const MatrixAny: any = Matrix;
    MatrixAny.Row = jest.fn(({children}) => <div>{children}</div>);
    MatrixAny.Item = jest.fn(() => null);

    return {
        Heading,
        Loader,
        Matrix,
        Toggler,
    };
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key, options = {}) => options.system ? `${key}:${options.system}` : key),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn(),
}));

jest.mock('../../../stores/securityContextStore', () => ({
    suluSecuritySystem: 'Sulu',
    resourceKeyMapping: {snippets: 'sulu.global.snippets'},
    getAvailableActions: jest.fn(),
    getSecurityContextByResourceKey: jest.fn(),
    getSystems: jest.fn(),
}));

RolePermissions.suluSecuritySystem = 'Sulu';

function renderRolePermissions(props: Object = {}) {
    return render(
        <RolePermissions
            onChange={jest.fn()}
            resourceKey="snippets"
            value={{}}
            {...props}
        />
    );
}

function getTogglerProps(index: number) {
    const calls = ((Toggler: any).mock.calls: any);
    return calls[index][0];
}

function getMatrixProps(index: number) {
    const calls = ((Matrix: any).mock.calls: any);
    return calls[index][0];
}

function getHeadingLabels() {
    const calls = ((Heading: any).mock.calls: any);
    return calls.map(([props]) => props.label);
}

function getLatestTogglerPropsForSystem(system: string) {
    const headingCalls = ((Heading: any).mock.calls: any);
    const togglerCalls = ((Toggler: any).mock.calls: any);

    for (let i = headingCalls.length - 1; i >= 0; i--) {
        if (headingCalls[i][0].label === `sulu_security.system_permission_heading:${system}`) {
            return togglerCalls[i][0];
        }
    }

    throw new Error(`Could not find toggler for system ${system}`);
}

function getLatestMatrixPropsForRole(roleId: string) {
    const calls = ((Matrix: any).mock.calls: any);

    for (let i = calls.length - 1; i >= 0; i--) {
        const props = calls[i][0];

        if (props.values && props.values[roleId] !== undefined) {
            return props;
        }
    }

    throw new Error(`Could not find matrix for role ${roleId}`);
}

async function resolveRoles(rolePromise: Promise<*>) {
    await act(async() => {
        await rolePromise;
    });
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render matrix with correct given values', async() => {
    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {id: 1, name: 'Admin', system: 'Sulu'},
                    {id: 2, name: 'Contact Manager', system: 'Sulu'},
                ],
            },
        }
    );
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'security']);
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website']);

    const value = {
        '1': {
            view: true,
            add: false,
            edit: true,
            delete: true,
        },
        '2': {
            view: true,
            add: true,
            edit: true,
            delete: false,
        },
    };

    const {container} = renderRolePermissions({value});

    expect(container).toMatchSnapshot();

    await resolveRoles(rolePromise);

    expect(container).toMatchSnapshot();
});

test('Hide system if specific system is given', async() => {
    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {id: 1, name: 'Admin', system: 'Sulu'},
                    {id: 2, name: 'Contact Manager', system: 'Website'},
                    {id: 3, name: 'Blog Manager', system: 'Blog'},
                ],
            },
        }
    );
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'security']);
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website', 'Blog']);

    renderRolePermissions({
        permissionCheck: true,
        system: 'Blog',
    });

    await resolveRoles(rolePromise);

    expect(getHeadingLabels()).toEqual([
        'sulu_security.system_permission_heading:Sulu',
        'sulu_security.system_permission_heading:Blog',
    ]);
});

test(
    'Show only Sulu system if specific system is given and permissionCheck is set to false for that system',
    async() => {
        const rolePromise = Promise.resolve(
            {
                _embedded: {
                    roles: [
                        {id: 1, name: 'Admin', system: 'Sulu'},
                        {id: 2, name: 'Contact Manager', system: 'Website'},
                        {id: 3, name: 'Blog Manager', system: 'Blog'},
                    ],
                },
            }
        );
        ResourceRequester.get.mockReturnValue(rolePromise);

        securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'security']);
        securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website', 'Blog']);

        renderRolePermissions({
            permissionCheck: false,
            system: 'Blog',
        });

        await resolveRoles(rolePromise);

        expect(getHeadingLabels()).toEqual([
            'sulu_security.system_permission_heading:Sulu',
        ]);
    }
);

test('Hide system if no actions are given', async() => {
    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {id: 1, name: 'Admin', system: 'Sulu'},
                    {id: 2, name: 'Contact Manager', system: 'Website'},
                ],
            },
        }
    );
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

    renderRolePermissions();

    await resolveRoles(rolePromise);

    expect(getHeadingLabels()).toEqual([
        'sulu_security.system_permission_heading:Sulu',
    ]);
});

test('Call onChange callback when value changes', async() => {
    const changeSpy = jest.fn();

    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {id: 1, name: 'Administrator', permissions: [], system: 'Sulu'},
                    {id: 2, name: 'Account Manager', permissions: [], system: 'Sulu'},
                ],
            },
        }
    );
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'live', 'security']);
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website']);

    const value = {
        '1': {
            view: true,
            add: true,
            edit: true,
            delete: true,
        },
    };

    renderRolePermissions({
        onChange: changeSpy,
        value,
    });

    await resolveRoles(rolePromise);

    expect(securityContextStore.getAvailableActions).toBeCalledWith('snippets', 'Sulu');
    expect(securityContextStore.getAvailableActions).toBeCalledWith('snippets', 'Website');

    act(() => {
        getMatrixProps(0).onChange({
            '2': {
                view: true,
                add: true,
                edit: true,
                delete: false,
            },
        });
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

    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {id: 1, name: 'Website User', permissions: [], system: 'Website'},
                    {id: 2, name: 'Account Manager', permissions: [], system: 'Sulu'},
                    {id: 3, name: 'Website Manager', permissions: [], system: 'Website'},
                    {id: 4, name: 'Administrator', permissions: [], system: 'Sulu'},
                ],
            },
        }
    );
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'live', 'security']);
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website']);

    const value = {
        '1': {
            view: true,
            add: true,
            edit: true,
            delete: true,
        },
        '2': {
            view: true,
            add: true,
            edit: true,
            delete: true,
        },
        '3': {
            view: true,
            add: true,
            edit: true,
            delete: false,
        },
        '4': {
            view: true,
            add: true,
            edit: true,
            delete: false,
        },
    };

    renderRolePermissions({
        onChange: changeSpy,
        value,
    });

    await resolveRoles(rolePromise);

    act(() => {
        getTogglerProps(0).onChange(false);
    });

    expect(changeSpy).toHaveBeenLastCalledWith({
        '1': {
            view: true,
            add: true,
            edit: true,
            delete: true,
        },
        '3': {
            view: true,
            add: true,
            edit: true,
            delete: false,
        },
    });
});

test('Call onChange callback when new matrix for system is added', async() => {
    const changeSpy = jest.fn();

    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {id: 1, name: 'Website User', permissions: [], system: 'Website'},
                    {id: 2, name: 'Account Manager', permissions: [], system: 'Sulu'},
                    {id: 3, name: 'Website Manager', permissions: [], system: 'Website'},
                    {id: 4, name: 'Administrator', permissions: [], system: 'Sulu'},
                ],
            },
        }
    );
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'live']);
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website']);

    const value = {
        '1': {
            view: true,
            add: true,
            edit: true,
            delete: true,
        },
        '3': {
            view: true,
            add: true,
            edit: true,
            delete: false,
        },
    };

    renderRolePermissions({
        onChange: changeSpy,
        value,
    });

    await resolveRoles(rolePromise);

    expect(getLatestTogglerPropsForSystem('Sulu').checked).toEqual(false);
    expect(getLatestTogglerPropsForSystem('Website').checked).toEqual(true);

    act(() => {
        getLatestTogglerPropsForSystem('Sulu').onChange(true);
    });

    const suluMatrix = getLatestMatrixPropsForRole('2');

    act(() => {
        suluMatrix.onChange({
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
        });
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

test('Use context for getting default values', async() => {
    const changeSpy = jest.fn();

    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {
                        id: 1,
                        name: 'Administrator',
                        permissions: [
                            {
                                context: 'sulu.pages.website',
                                permissions: {add: true, delete: false, edit: true, live: false, view: true},
                            },
                        ],
                        system: 'Sulu',
                    },
                ],
            },
        }
    );
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'live']);
    securityContextStore.getSystems.mockReturnValue(['Sulu']);
    securityContextStore.getSecurityContextByResourceKey.mockReturnValue('sulu.pages.website');

    renderRolePermissions({
        onChange: changeSpy,
        resourceKey: 'pages',
        value: {},
    });

    await resolveRoles(rolePromise);

    expect(getTogglerProps(0).checked).toEqual(false);

    act(() => {
        getTogglerProps(0).onChange(true);
    });

    expect(securityContextStore.getSecurityContextByResourceKey).toBeCalledWith('pages');

    expect(getLatestMatrixPropsForRole('1').values).toEqual({
        '1': {
            add: true,
            delete: false,
            edit: true,
            live: false,
            view: true,
        },
    });
});

test('Use context with replaced webspace for getting default values', async() => {
    const changeSpy = jest.fn();

    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {
                        id: 1,
                        name: 'Administrator',
                        permissions: [
                            {
                                context: 'sulu.pages.website',
                                permissions: {add: true, delete: false, edit: true, live: false, view: true},
                            },
                        ],
                        system: 'Sulu',
                    },
                ],
            },
        }
    );
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'live']);
    securityContextStore.getSystems.mockReturnValue(['Sulu']);
    securityContextStore.getSecurityContextByResourceKey.mockReturnValue('sulu.pages.#webspace#');

    renderRolePermissions({
        onChange: changeSpy,
        resourceKey: 'pages',
        value: {},
        webspaceKey: 'website',
    });

    await resolveRoles(rolePromise);

    expect(getTogglerProps(0).checked).toEqual(false);

    act(() => {
        getTogglerProps(0).onChange(true);
    });

    expect(securityContextStore.getSecurityContextByResourceKey).toBeCalledWith('pages');

    expect(getLatestMatrixPropsForRole('1').values).toEqual({
        '1': {
            add: true,
            delete: false,
            edit: true,
            live: false,
            view: true,
        },
    });
});
