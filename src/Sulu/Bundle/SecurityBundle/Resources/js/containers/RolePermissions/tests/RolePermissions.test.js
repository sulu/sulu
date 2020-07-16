// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import securityContextStore from '../../../stores/securityContextStore';
import RolePermissions from '../RolePermissions';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn(),
}));

jest.mock('../../../stores/securityContextStore', () => ({
    getAvailableActions: jest.fn(),
    getSystems: jest.fn(),
}));

beforeEach(() => {
    RolePermissions.resourceKeyMapping = {snippets: 'sulu.global.snippets'};
});

test('Render matrix with correct all values selected if not given', () => {
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

    const value = {};
    const rolePermissions = mount(<RolePermissions onChange={jest.fn()} resourceKey="snippets" value={value} />);

    expect(rolePermissions.render()).toMatchSnapshot();

    return Promise.all([rolePromise]).then(() => {
        rolePermissions.update();
        expect(rolePermissions.render()).toMatchSnapshot();
    });
});

test('Render matrix with correct given values', () => {
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
    const rolePermissions = mount(<RolePermissions onChange={jest.fn()} resourceKey="snippets" value={value} />);

    expect(rolePermissions.render()).toMatchSnapshot();

    return Promise.all([rolePromise]).then(() => {
        rolePermissions.update();
        expect(rolePermissions.render()).toMatchSnapshot();
    });
});

test('Render matrix with correct default values from roles', () => {
    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {
                        id: 1,
                        name: 'Admin',
                        permissions: [
                            {
                                context: 'sulu.global.snippets',
                                permissions: {
                                    view: true,
                                    add: true,
                                    edit: true,
                                    delete: false,
                                    security: true,
                                },
                            },
                        ],
                        system: 'Sulu',
                    },
                    {
                        id: 2,
                        name: 'Contact Manager',
                        permissions: [
                            {
                                context: 'sulu.contact.people',
                                permissions: {
                                    view: true,
                                    add: true,
                                    edit: true,
                                    delete: true,
                                    security: true,
                                },
                            },
                            {
                                context: 'sulu.global.snippets',
                                permissions: {
                                    view: true,
                                    add: true,
                                    edit: false,
                                    delete: false,
                                    security: true,
                                },
                            },
                        ],
                        system: 'Sulu',
                    },
                    {
                        id: 3,
                        name: 'Website User',
                        permissions: [
                            {
                                context: 'sulu.contact.people',
                                permissions: {
                                    view: true,
                                    add: true,
                                    edit: true,
                                    delete: true,
                                    security: true,
                                },
                            },
                            {
                                context: 'sulu.global.snippets',
                                permissions: {
                                    view: true,
                                    add: true,
                                    edit: false,
                                    delete: false,
                                    security: true,
                                },
                            },
                        ],
                        system: 'Website',
                    },
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
            return ['view'];
        }
    });
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website']);

    const rolePermissions = shallow(<RolePermissions onChange={jest.fn()} resourceKey="snippets" value={{}} />);

    return Promise.all([rolePromise]).then(() => {
        rolePermissions.update();
        expect(rolePermissions.find('SystemRolePermissions').at(0).prop('values')).toEqual({
            '1': {
                add: true,
                delete: false,
                edit: true,
                security: true,
                view: true,
            },
            '2': {
                add: true,
                delete: false,
                edit: false,
                security: true,
                view: true,
            },
        });
        expect(rolePermissions.find('SystemRolePermissions').at(1).prop('values')).toEqual({
            '3': {
                view: true,
            },
        });
    });
});

test('Call onChange callback when value changes', () => {
    const changeSpy = jest.fn();

    const rolePromise = Promise.resolve(
        {
            _embedded: {
                roles: [
                    {id: 1, name: 'Administrator', permissions: [], system: 'Sulu'},
                    {id: 2, name: 'Account Manager', permissions: [], system: 'Website'},
                ],
            },
        }
    );
    ResourceRequester.get.mockReturnValue(rolePromise);

    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit', 'delete', 'live', 'security']);
    securityContextStore.getSystems.mockReturnValue(['Sulu', 'Website']);

    const value = {
        '3': {
            view: true,
            add: true,
            edit: true,
            delete: true,
        },
    };
    const rolePermissions = mount(<RolePermissions onChange={changeSpy} resourceKey="snippets" value={value} />);

    return Promise.all([rolePromise]).then(() => {
        rolePermissions.update();
        expect(securityContextStore.getAvailableActions).toBeCalledWith('snippets', 'Sulu');
        expect(securityContextStore.getAvailableActions).toBeCalledWith('snippets', 'Website');

        rolePermissions.find('Matrix').at(1).prop('onChange')({
            '2': {
                view: true,
                add: true,
                edit: true,
                delete: false,
            },
        });
        expect(changeSpy).toHaveBeenLastCalledWith({
            '3': {
                view: true,
                add: true,
                edit: true,
                delete: true,
            },
            '2': {
                view: true,
                add: true,
                edit: true,
                delete: false,
            },
        });

        rolePermissions.find('Matrix').at(0).prop('onChange')({
            '1': {
                view: true,
                add: false,
                edit: true,
                delete: true,
            },
        });
        expect(changeSpy).toHaveBeenLastCalledWith({
            '3': {
                view: true,
                add: true,
                edit: true,
                delete: true,
            },
            '1': {
                view: true,
                add: false,
                edit: true,
                delete: true,
            },
        });
    });
});
