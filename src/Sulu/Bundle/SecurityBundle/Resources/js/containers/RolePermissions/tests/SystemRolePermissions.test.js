// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import securityContextStore from '../../../stores/securityContextStore';
import SystemRolePermissions from '../SystemRolePermissions';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');
    const actual = jest.requireActual('sulu-admin-bundle/components');

    const Matrix: any = jest.fn(function MatrixMock({children}) {
        return <div>{children}</div>;
    });
    Matrix.Row = function MatrixRowMock({children}) {
        return <div>{children}</div>;
    };
    Matrix.Item = function MatrixItemMock() {
        return null;
    };

    return {
        ...actual,
        Matrix,
    };
});

jest.mock('../../../stores/securityContextStore', () => ({
    getAvailableActions: jest.fn(),
    getSecurityContextByResourceKey: jest.fn(),
}));

const componentsMock = ((jest.requireMock('sulu-admin-bundle/components'): any): {
    Matrix: {mock: {calls: Array<[Object]>}},
    ...
});

function getLatestMatrixProps(): any {
    return getLatestMockProps(componentsMock.Matrix);
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render permissions for a single system', () => {
    const roles = [
        {id: 2, identifier: '', name: 'User', permissions: [], system: 'Sulu'},
        {id: 3, identifier: '', name: 'Contact Manager', permissions: [], system: 'Sulu'},
    ];

    const {asFragment} = render(
        <SystemRolePermissions
            actions={['view', 'add', 'edit']}
            disabled={false}
            onChange={jest.fn()}
            resourceKey="test"
            roles={roles}
            system="Sulu"
            values={{'2': {view: true, add: false, edit: true}, '3': {view: false, add: true, edit: false}}}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Do not show Matrix if no values are given', () => {
    const roles = [
        {id: 2, identifier: '', name: 'User', permissions: [], system: 'Sulu'},
        {id: 3, identifier: '', name: 'Contact Manager', permissions: [], system: 'Sulu'},
    ];

    render(
        <SystemRolePermissions
            actions={['view', 'add', 'edit']}
            disabled={false}
            onChange={jest.fn()}
            resourceKey="test"
            roles={roles}
            system="Sulu"
            values={{}}
        />
    );

    expect(componentsMock.Matrix).toHaveBeenCalledTimes(0);
    expect(screen.getByRole('checkbox')).not.toBeChecked();
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

    expect(getLatestMatrixProps().disabled).toEqual(true);
});

test('Call onChange callback when matrix changes', async() => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();

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

    await user.click(screen.getByRole('checkbox'));

    const newValue = {'1': {view: true}};
    act(() => {
        getLatestMatrixProps().onChange(newValue);
    });

    expect(changeSpy).toBeCalledWith(newValue, 'Sulu');
});

test('Call onChange callback with empty values if toggler is deactivated', async() => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();

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

    await user.click(screen.getByRole('checkbox'));

    expect(changeSpy).toBeCalledWith({}, 'Sulu');
});

test('Show default values after activating toggler', async() => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();

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

    expect(componentsMock.Matrix).toHaveBeenCalledTimes(0);

    await user.click(screen.getByRole('checkbox'));

    expect(componentsMock.Matrix).toHaveBeenCalledTimes(1);
    expect(getLatestMatrixProps().values).toEqual({
        '2': {view: true, add: true, edit: true},
        '3': {view: true, add: false, edit: true},
    });
});
