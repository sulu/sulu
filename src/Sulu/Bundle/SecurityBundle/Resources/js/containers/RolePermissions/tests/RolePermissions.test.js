// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import securityContextStore from '../../../stores/securityContextStore';
import RolePermissions from '../RolePermissions';

jest.mock('sulu-admin-bundle/utils/Translator');

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

async function waitForSystems(count: number) {
    await waitFor(() => expect(screen.getAllByRole('checkbox')).toHaveLength(count));
}

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
    const {container} = render(<RolePermissions onChange={jest.fn()} resourceKey="snippets" value={value} />);

    expect(container).toMatchSnapshot();

    await rolePromise;
    await waitForSystems(1);
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

    render(
        <RolePermissions
            onChange={jest.fn()}
            permissionCheck={true}
            resourceKey="snippets"
            system="Blog"
            value={{
                '1': {view: true},
                '2': {view: true},
                '3': {view: true},
            }}
        />
    );

    await rolePromise;
    await waitForSystems(2);

    expect(screen.getByText('Admin')).toBeInTheDocument();
    expect(screen.queryByText('Contact Manager')).not.toBeInTheDocument();
    expect(screen.getByText('Blog Manager')).toBeInTheDocument();
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

        render(
            <RolePermissions
                onChange={jest.fn()}
                permissionCheck={false}
                resourceKey="snippets"
                system="Blog"
                value={{
                    '1': {view: true},
                    '2': {view: true},
                    '3': {view: true},
                }}
            />
        );

        await rolePromise;
        await waitForSystems(1);

        expect(screen.getByText('Admin')).toBeInTheDocument();
        expect(screen.queryByText('Contact Manager')).not.toBeInTheDocument();
        expect(screen.queryByText('Blog Manager')).not.toBeInTheDocument();
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
    });
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website']);

    render(
        <RolePermissions
            onChange={jest.fn()}
            resourceKey="snippets"
            value={{'1': {view: true}}}
        />
    );

    await rolePromise;
    await waitForSystems(1);

    expect(screen.getByText('Admin')).toBeInTheDocument();
    expect(screen.queryByText('Contact Manager')).not.toBeInTheDocument();
});

test('Call onChange callback when value changes', async() => {
    const user = userEvent.setup();
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
    render(
        <RolePermissions onChange={changeSpy} resourceKey="snippets" value={value} />
    );

    await rolePromise;
    await waitForSystems(1);

    expect(securityContextStore.getAvailableActions).toHaveBeenCalledWith('snippets', 'Sulu');
    expect(securityContextStore.getAvailableActions).toHaveBeenCalledWith('snippets', 'Website');

    await user.click(screen.getAllByTitle('Delete')[0]);

    expect(changeSpy).toHaveBeenLastCalledWith({
        '1': {
            view: true,
            add: true,
            edit: true,
            delete: false,
        },
    });
});

test('Call onChange callback when matrix for system is deactivated', async() => {
    const user = userEvent.setup();
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
    render(
        <RolePermissions onChange={changeSpy} resourceKey="snippets" value={value} />
    );

    await rolePromise;
    await waitForSystems(2);

    await user.click(screen.getAllByRole('checkbox')[0]);

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
    const user = userEvent.setup();
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
    render(
        <RolePermissions onChange={changeSpy} resourceKey="snippets" value={value} />
    );

    await rolePromise;
    await waitForSystems(2);

    expect(screen.queryByText('Account Manager')).not.toBeInTheDocument();
    expect(screen.getByText('Website User')).toBeInTheDocument();
    expect(screen.getByText('Website Manager')).toBeInTheDocument();

    await user.click(screen.getAllByRole('checkbox')[0]);
    expect(screen.getByText('Account Manager')).toBeInTheDocument();
    expect(screen.getByText('Administrator')).toBeInTheDocument();

    await user.click(screen.getAllByTitle('View')[0]);

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
    const user = userEvent.setup();
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

    const value = {};
    render(
        <RolePermissions onChange={changeSpy} resourceKey="pages" value={value} />
    );

    await rolePromise;
    await waitForSystems(1);

    expect(screen.getByRole('checkbox')).not.toBeChecked();

    await user.click(screen.getByRole('checkbox'));
    expect(securityContextStore.getSecurityContextByResourceKey).toHaveBeenCalledWith('pages');
    expect(screen.getByRole('table')).toBeInTheDocument();

    expect(screen.getByTitle('Add')).toHaveClass('selected');
    expect(screen.getByTitle('Delete')).not.toHaveClass('selected');
    expect(screen.getByTitle('Edit')).toHaveClass('selected');
    expect(screen.getByTitle('Live')).not.toHaveClass('selected');
    expect(screen.getByTitle('View')).toHaveClass('selected');
});

test('Use context with replaced webspace for getting default values', async() => {
    const user = userEvent.setup();
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

    const value = {};
    render(
        <RolePermissions onChange={changeSpy} resourceKey="pages" value={value} webspaceKey="website" />
    );

    await rolePromise;
    await waitForSystems(1);

    expect(screen.getByRole('checkbox')).not.toBeChecked();

    await user.click(screen.getByRole('checkbox'));
    expect(securityContextStore.getSecurityContextByResourceKey).toHaveBeenCalledWith('pages');
    expect(screen.getByRole('table')).toBeInTheDocument();

    expect(screen.getByTitle('Add')).toHaveClass('selected');
    expect(screen.getByTitle('Delete')).not.toHaveClass('selected');
    expect(screen.getByTitle('Edit')).toHaveClass('selected');
    expect(screen.getByTitle('Live')).not.toHaveClass('selected');
    expect(screen.getByTitle('View')).toHaveClass('selected');
});
