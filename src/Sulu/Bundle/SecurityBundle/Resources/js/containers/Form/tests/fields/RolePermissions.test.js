// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import RolePermissions from '../../fields/RolePermissions';

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn());
jest.mock(
    'sulu-admin-bundle/containers/Form/stores/ResourceFormStore',
    () => jest.fn(function(resourceStore, formKey, options) {
        this.options = options;
    })
);
jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.options = formStore.options;
}));

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'), 'snippets', {resourceKey: 'snippets'}
        )
    );

    const value = {};

    const rolePermissions = shallow(
        <RolePermissions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(rolePermissions.find('RolePermissions').prop('disabled')).toEqual(false);
    expect(rolePermissions.find('RolePermissions').prop('resourceKey')).toEqual('snippets');
    expect(rolePermissions.find('RolePermissions').prop('value')).toBe(value);
});

test('Pass disabled prop correctly to component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'), 'snippets', {resourceKey: 'snippets'}
        )
    );

    const rolePermissions = shallow(
        <RolePermissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(rolePermissions.find('RolePermissions').prop('disabled')).toEqual(true);
    expect(rolePermissions.find('RolePermissions').prop('value')).toEqual({});
});

test('Pass disabled prop correctly to component', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'), 'snippets', {resourceKey: 'snippets'}
        )
    );

    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const rolePermissions = shallow(
        <RolePermissions
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    rolePermissions.find('RolePermissions').prop('onChange')({});

    expect(changeSpy).toBeCalledWith({});
    expect(finishSpy).toBeCalledWith();
});
