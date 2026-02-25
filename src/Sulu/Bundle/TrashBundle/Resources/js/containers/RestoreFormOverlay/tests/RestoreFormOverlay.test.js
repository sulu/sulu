// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import SchemaFormStoreDecorator from 'sulu-admin-bundle/containers/Form/stores/SchemaFormStoreDecorator';
import MemoryFormStore from 'sulu-admin-bundle/containers/Form/stores/MemoryFormStore';
import RestoreFormOverlay from '../RestoreFormOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock(
    'sulu-admin-bundle/containers/Form/stores/SchemaFormStoreDecorator',
    () => jest.fn(function(initializer) {
        return initializer({}, {});
    })
);

jest.mock(
    'sulu-admin-bundle/containers/Form/stores/MemoryFormStore',
    () => jest.fn(function() {
        this.data = {};
        this.destroy = jest.fn();
        this.changeMultiple = jest.fn();
        this.loading = false;
        this.save = jest.fn().mockReturnValue(Promise.resolve());
    })
);

jest.mock('sulu-admin-bundle/containers/Form/Form', () => {
    const React = require('react');

    return class FormMock extends React.Component<*> {
        submit() {
            this.props.onSubmit();
        }

        render() {
            return React.createElement('div', undefined, 'form container mock');
        }
    };
});

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue(Promise.resolve({restoreData: {}})),
}));

const memoryFormStoreMock = ((MemoryFormStore: any): {
    mock: {instances: Array<Object>},
    ...
});

beforeEach(() => {
    jest.clearAllMocks();
});

test('Component should render', () => {
    const {baseElement} = render(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Component should not render without formKey', () => {
    const {baseElement} = render(
        <RestoreFormOverlay
            formKey={null}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Component should not render without trashItemId', () => {
    const {baseElement} = render(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            trashItemId={null}
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Component should call close callback', async() => {
    const user = userEvent.setup();
    const onClose = jest.fn();

    render(
        <RestoreFormOverlay
            formKey="test"
            onClose={onClose}
            onConfirm={jest.fn()}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    await user.click(screen.getAllByLabelText('su-times')[0]);

    expect(onClose).toHaveBeenCalled();
});

test('Component should call confirm callback', async() => {
    const user = userEvent.setup();
    const onConfirm = jest.fn();

    render(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={onConfirm}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    const data = {foo: 'bar'};
    const formStore = memoryFormStoreMock.mock.instances[0];
    formStore.data = data;

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(onConfirm).toHaveBeenCalledWith(data);
});

test('Component should create formStore, load restore data and set it to the formstore', () => {
    const trashItemPromise = Promise.resolve({
        id: 5,
        resourceKey: 'categories',
        resourceId: '33',
        restoreData: {
            key: 'test-key',
            parentId: 32,
        },
    });
    ResourceRequester.get.mockReturnValue(trashItemPromise);

    render(
        <RestoreFormOverlay
            formKey="test-form-key"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    const formStore = memoryFormStoreMock.mock.instances[0];
    expect(SchemaFormStoreDecorator).toBeCalledWith(expect.anything(), 'test-form-key');
    expect(ResourceRequester.get).toBeCalledWith('trash_items', {id: 'trash-item-123'});
    expect(formStore.changeMultiple).not.toBeCalled();
    expect(formStore.loading).toBeTruthy();

    return trashItemPromise.then(() => {
        expect(formStore.changeMultiple).toBeCalledWith(
            {key: 'test-key', parentId: 32},
            {isServerValue: true}
        );
        expect(formStore.loading).toBeFalsy();
    });
});

test('Component should update formStore on changing form key', () => {
    const onClose = jest.fn();
    const onConfirm = jest.fn();
    const {rerender} = render(
        <RestoreFormOverlay
            formKey="test"
            onClose={onClose}
            onConfirm={onConfirm}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    const formStore = memoryFormStoreMock.mock.instances[0];
    expect(SchemaFormStoreDecorator).toBeCalledTimes(1);
    expect(SchemaFormStoreDecorator).toBeCalledWith(expect.anything(), 'test');
    expect(formStore.destroy).not.toHaveBeenCalled();

    rerender(
        <RestoreFormOverlay
            formKey="other"
            onClose={onClose}
            onConfirm={onConfirm}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    expect(SchemaFormStoreDecorator).toBeCalledTimes(2);
    expect(SchemaFormStoreDecorator).toBeCalledWith(expect.anything(), 'other');
    expect(formStore.destroy).toHaveBeenCalled();

    const newFormStore = memoryFormStoreMock.mock.instances[1];
    expect(newFormStore).not.toBe(formStore);
    expect(newFormStore.destroy).not.toHaveBeenCalled();
});

test('Component should update formStore on reopen', () => {
    const onClose = jest.fn();
    const onConfirm = jest.fn();
    const {rerender} = render(
        <RestoreFormOverlay
            formKey="test"
            onClose={onClose}
            onConfirm={onConfirm}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    const formStore = memoryFormStoreMock.mock.instances[0];

    rerender(
        <RestoreFormOverlay
            formKey="test"
            onClose={onClose}
            onConfirm={onConfirm}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    expect(formStore.destroy).toHaveBeenCalled();

    const newFormStore = memoryFormStoreMock.mock.instances[1];
    expect(newFormStore).not.toBe(formStore);
    expect(newFormStore.destroy).not.toHaveBeenCalled();
});

test('Component should destroy formStore on unmount', () => {
    const {unmount} = render(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    const formStore = memoryFormStoreMock.mock.instances[0];

    unmount();

    expect(formStore.destroy).toHaveBeenCalled();
});
