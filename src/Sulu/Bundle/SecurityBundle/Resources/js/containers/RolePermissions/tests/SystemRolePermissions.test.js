// @flow
import {render} from '@testing-library/react';
import React from 'react';
import {Matrix, Toggler} from 'sulu-admin-bundle/components';
import securityContextStore from '../../../stores/securityContextStore';
import SystemRolePermissions from '../SystemRolePermissions';

jest.mock('sulu-admin-bundle/components', () => {
    const MatrixMock = jest.fn(() => null);
    (MatrixMock: any).Row = jest.fn(() => null);
    (MatrixMock: any).Item = jest.fn(() => null);

    return {
        Heading: jest.fn(({children}) => <div>{children}</div>),
        Matrix: MatrixMock,
        Toggler: jest.fn(() => null),
    };
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores/securityContextStore', () => ({
    getAvailableActions: jest.fn(),
    getSecurityContextByResourceKey: jest.fn(),
}));

beforeEach(() => {
    jest.clearAllMocks();
});

function createRoles() {
    return [
        {id: 2, identifier: '', name: 'User', permissions: [], system: 'Sulu'},
        {id: 3, identifier: '', name: 'Contact Manager', permissions: [], system: 'Sulu'},
    ];
}

test('Render permissions for a single system', () => {
    render(
        <SystemRolePermissions
            actions={['view', 'add', 'edit']}
            disabled={false}
            onChange={jest.fn()}
            resourceKey="test"
            roles={createRoles()}
            system="Sulu"
            values={{'2': {view: true, add: false, edit: true}, '3': {view: false, add: true, edit: false}}}
        />
    );

    const togglerCalls = (Toggler: any).mock.calls;
    const [togglerProps] = togglerCalls[togglerCalls.length - 1];
    expect(togglerProps.checked).toEqual(true);
    expect(Matrix).toHaveBeenCalledTimes(1);
});

test('Do not show Matrix if no values are given', () => {
    render(
        <SystemRolePermissions
            actions={['view', 'add', 'edit']}
            disabled={false}
            onChange={jest.fn()}
            resourceKey="test"
            roles={createRoles()}
            system="Sulu"
            values={{}}
        />
    );

    const togglerCalls = (Toggler: any).mock.calls;
    const [togglerProps] = togglerCalls[togglerCalls.length - 1];
    expect(togglerProps.checked).toEqual(false);
    expect(Matrix).toHaveBeenCalledTimes(0);
});

test('Render permissions for a single system in disabled state', () => {
    render(
        <SystemRolePermissions
            actions={[]}
            disabled={true}
            onChange={jest.fn()}
            resourceKey="test"
            roles={[]}
            system="Sulu"
            values={{'2': {view: true, add: false, edit: true}}}
        />
    );

    const [matrixProps] = (Matrix: any).mock.calls[0];
    expect(matrixProps.disabled).toEqual(true);
});

test('Call onChange callback when matrix changes', () => {
    const changeSpy = jest.fn();

    render(
        <SystemRolePermissions
            actions={['view']}
            disabled={false}
            onChange={changeSpy}
            resourceKey="test"
            roles={[]}
            system="Sulu"
            values={{}}
        />
    );

    const togglerCalls = (Toggler: any).mock.calls;
    const [togglerProps] = togglerCalls[togglerCalls.length - 1];
    togglerProps.onChange(true);

    const [matrixProps] = (Matrix: any).mock.calls[0];
    const newValue = {'1': {view: true}};
    matrixProps.onChange(newValue);

    expect(changeSpy).toBeCalledWith(newValue, 'Sulu');
});

test('Call onChange callback with empty values if toggler is deactivated', () => {
    const changeSpy = jest.fn();

    render(
        <SystemRolePermissions
            actions={['view']}
            disabled={false}
            onChange={changeSpy}
            resourceKey="test"
            roles={[]}
            system="Sulu"
            values={{'1': {view: true}}}
        />
    );

    const togglerCalls = (Toggler: any).mock.calls;
    const [togglerProps] = togglerCalls[togglerCalls.length - 1];
    togglerProps.onChange(false);

    expect(changeSpy).toBeCalledWith({}, 'Sulu');
});

test('Show default values after activating toggler', () => {
    const changeSpy = jest.fn();
    const roles = [
        {
            id: 2,
            identifier: '',
            name: 'User',
            permissions: [
                {context: 'sulu.test', permissions: {view: true, add: true, edit: true}},
            ],
            system: 'Sulu',
        },
        {
            id: 3,
            identifier: '',
            name: 'Contact Manager',
            permissions: [
                {context: 'sulu.test', permissions: {view: true, add: false, edit: true}},
            ],
            system: 'Sulu',
        },
    ];

    securityContextStore.getSecurityContextByResourceKey.mockImplementation((resourceKey) => {
        switch (resourceKey) {
            case 'test':
                return 'sulu.test';
            default:
                return undefined;
        }
    });
    securityContextStore.getAvailableActions.mockReturnValue(['view', 'add', 'edit']);

    render(
        <SystemRolePermissions
            actions={['view']}
            disabled={false}
            onChange={changeSpy}
            resourceKey="test"
            roles={roles}
            system="Sulu"
            values={{}}
        />
    );

    expect(Matrix).toHaveBeenCalledTimes(0);

    const togglerCalls = (Toggler: any).mock.calls;
    const [togglerProps] = togglerCalls[togglerCalls.length - 1];
    togglerProps.onChange(true);

    expect(Matrix).toHaveBeenCalledTimes(1);
    const [matrixProps] = (Matrix: any).mock.calls[0];
    expect(matrixProps.values).toEqual({
        '2': {view: true, add: true, edit: true},
        '3': {view: true, add: false, edit: true},
    });
});
