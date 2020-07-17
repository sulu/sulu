// @flow
import React from 'react';
import {mount} from 'enzyme';
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
    resourceKeyMapping: {snippets: 'sulu.global.snippets'},
    getAvailableActions: jest.fn(),
    getSecurityContextByResourceKey: jest.fn(),
    getSystems: jest.fn(),
}));

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

test('Call onChange callback when value changes', () => {
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
    const rolePermissions = mount(<RolePermissions onChange={changeSpy} resourceKey="snippets" value={value} />);

    return Promise.all([rolePromise]).then(() => {
        rolePermissions.update();
        expect(securityContextStore.getAvailableActions).toBeCalledWith('snippets', 'Sulu');
        expect(securityContextStore.getAvailableActions).toBeCalledWith('snippets', 'Website');

        rolePermissions.find('Matrix').at(0).prop('onChange')({
            '2': {
                view: true,
                add: true,
                edit: true,
                delete: false,
            },
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
});

test('Call onChange callback when matrix for system is deactivated', () => {
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
    const rolePermissions = mount(<RolePermissions onChange={changeSpy} resourceKey="snippets" value={value} />);

    return Promise.all([rolePromise]).then(() => {
        rolePermissions.update();

        rolePermissions.find('Toggler').at(0).prop('onChange')(false);

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
});

test('Call onChange callback when new matrix for system is added', () => {
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
    const rolePermissions = mount(<RolePermissions onChange={changeSpy} resourceKey="snippets" value={value} />);

    return Promise.all([rolePromise]).then(() => {
        rolePermissions.update();

        expect(rolePermissions.find('Toggler').at(0).prop('checked')).toEqual(false);
        expect(rolePermissions.find('Toggler').at(1).prop('checked')).toEqual(true);

        rolePermissions.find('Toggler').at(0).prop('onChange')(true);
        rolePermissions.update();
        expect(rolePermissions.find('Matrix')).toHaveLength(2);

        rolePermissions.find('Matrix').find('Row[name="2"] Item[icon="su-eye"] > div').simulate('click');

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
});
