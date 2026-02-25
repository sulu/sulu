// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceLocatorHistory} from 'sulu-route-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import CustomUrl from '../../fields/CustomUrl';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(resourceFormStore) {
        this.id = resourceFormStore.id;
        this.options = resourceFormStore.options;
        this.getValueByPath = jest.fn();
    }),
    ResourceFormStore: jest.fn(function(formStore, formKey, options = {}) {
        this.id = formStore.id;
        this.options = options;
    }),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id) {
        this.id = id;
    }),
}));

jest.mock('sulu-route-bundle/containers', () => ({
    ResourceLocatorHistory: jest.fn(() => null),
}));

const createFormInspector = (id: ?number = undefined) => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', id),
            'test',
            {webspace: 'sulu_io'}
        )
    );

    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/baseDomain':
                return '*.sulu.io/*';
        }
    });

    return formInspector;
};

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass correct props to CustomUrl component', () => {
    render(
        <CustomUrl
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            value={['a', 'b']}
        />
    );

    const inputs = screen.getAllByRole('textbox');

    expect(screen.getByText('.sulu.io/')).toBeInTheDocument();
    expect(inputs).toHaveLength(2);
    expect(inputs[0]).toHaveValue('a');
    expect(inputs[1]).toHaveValue('b');
    expect((ResourceLocatorHistory: any)).not.toHaveBeenCalled();
});

test('Pass correct props to ResourceLocatorHistory component if id an existing resource is loaded', () => {
    render(
        <CustomUrl
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector(2)}
            value={['a', 'b']}
        />
    );

    const inputs = screen.getAllByRole('textbox');

    expect(screen.getByText('.sulu.io/')).toBeInTheDocument();
    expect(inputs).toHaveLength(2);
    expect(inputs[0]).toHaveValue('a');
    expect(inputs[1]).toHaveValue('b');
    expect((ResourceLocatorHistory: any)).toHaveBeenCalledTimes(1);
    expect(getLatestMockProps((ResourceLocatorHistory: any)).options).toEqual({webspace: 'sulu_io', id: 2});
    expect(getLatestMockProps((ResourceLocatorHistory: any)).resourceKey).toEqual('custom_url_routes');
});

test('Pass correct props with empty value to CustomUrl component', () => {
    const formInspector = createFormInspector();
    formInspector.getValueByPath.mockImplementation((path) => path === '/baseDomain' ? 'sulu.io/*' : undefined);

    render(
        <CustomUrl
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={undefined}
        />
    );

    const inputs = screen.getAllByRole('textbox');

    expect(screen.getByText('sulu.io/')).toBeInTheDocument();
    expect(inputs).toHaveLength(1);
    expect(inputs[0]).toHaveValue('');
});

test('Call onChange when if a value changes', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const formInspector = createFormInspector();
    formInspector.getValueByPath.mockImplementation((path) => path === '/baseDomain' ? 'sulu.io/*' : undefined);
    render(
        <CustomUrl
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            value={undefined}
        />
    );

    const inputs = screen.getAllByRole('textbox');
    await user.click(inputs[0]);
    await user.paste('test');

    expect(changeSpy).toBeCalledWith(['test']);
});

test('Call onFinish when if the field is blurred', async() => {
    const user = userEvent.setup();
    const finishSpy = jest.fn();
    const formInspector = createFormInspector();
    formInspector.getValueByPath.mockImplementation((path) => path === '/baseDomain' ? 'sulu.io/*' : undefined);
    render(
        <CustomUrl
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFinish={finishSpy}
            value={undefined}
        />
    );

    const inputs = screen.getAllByRole('textbox');
    await user.click(inputs[0]);
    await user.tab();

    expect(finishSpy).toBeCalledWith();
});
