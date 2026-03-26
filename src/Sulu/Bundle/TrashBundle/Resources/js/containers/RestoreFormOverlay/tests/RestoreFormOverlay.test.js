// @flow
import {render, screen, waitFor} from '@testing-library/react';
import React from 'react';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import SchemaFormStoreDecorator from 'sulu-admin-bundle/containers/Form/stores/SchemaFormStoreDecorator';
import FormOverlay from 'sulu-admin-bundle/containers/FormOverlay';
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
    })
);

jest.mock('sulu-admin-bundle/containers/FormOverlay', () => jest.fn(() => (
    <div data-testid="form-overlay" />
)));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue(Promise.resolve({restoreData: {}})),
}));

beforeEach(() => {
    jest.clearAllMocks();
});

function renderRestoreFormOverlay(props: Object = {}) {
    return render(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            trashItemId="trash-item-123"
            {...props}
        />
    );
}

test('Component should render', async() => {
    renderRestoreFormOverlay();
    expect(await screen.findByTestId('form-overlay')).toBeInTheDocument();
    expect(FormOverlay).toHaveBeenCalledTimes(1);
});

test('Component should not render without formKey', () => {
    renderRestoreFormOverlay({formKey: null});
    expect(screen.queryByTestId('form-overlay')).not.toBeInTheDocument();
    expect(FormOverlay).not.toHaveBeenCalled();
});

test('Component should not render without trashItemId', () => {
    renderRestoreFormOverlay({trashItemId: null});
    expect(screen.queryByTestId('form-overlay')).not.toBeInTheDocument();
    expect(FormOverlay).not.toHaveBeenCalled();
});

test('Component should call close callback', async() => {
    const onClose = jest.fn();

    renderRestoreFormOverlay({onClose});
    expect(await screen.findByTestId('form-overlay')).toBeInTheDocument();

    const [formOverlayProps] = (FormOverlay: any).mock.calls[0];
    formOverlayProps.onClose();
    expect(onClose).toHaveBeenCalled();
});

test('Component should call confirm callback', async() => {
    const onConfirm = jest.fn();

    renderRestoreFormOverlay({onConfirm});
    expect(await screen.findByTestId('form-overlay')).toBeInTheDocument();

    const [formOverlayProps] = (FormOverlay: any).mock.calls[0];
    formOverlayProps.formStore.data = {foo: 'bar'};

    formOverlayProps.onConfirm();
    expect(onConfirm).toHaveBeenCalledWith({foo: 'bar'});
});

test('Component should create formStore, load restore data and set it to the formstore', async() => {
    let resolveTrashItemPromise;
    const trashItemPromise = new Promise((resolve) => {
        resolveTrashItemPromise = resolve;
    });
    ResourceRequester.get.mockReturnValue(trashItemPromise);

    renderRestoreFormOverlay({formKey: 'test-form-key', open: false});
    expect(await screen.findByTestId('form-overlay')).toBeInTheDocument();

    expect(SchemaFormStoreDecorator).toBeCalledWith(expect.anything(), 'test-form-key');
    expect(ResourceRequester.get).toBeCalledWith('trash_items', {id: 'trash-item-123'});

    const [formOverlayProps] = (FormOverlay: any).mock.calls[0];
    expect(formOverlayProps.formStore.changeMultiple).not.toBeCalled();
    expect(formOverlayProps.formStore.loading).toBeTruthy();

    if (!resolveTrashItemPromise) {
        throw new Error('Expected restore data promise resolver');
    }

    resolveTrashItemPromise({
        id: 5,
        resourceKey: 'categories',
        resourceId: '33',
        restoreData: {
            key: 'test-key',
            parentId: 32,
        },
    });
    await trashItemPromise;
    await waitFor(() => expect(formOverlayProps.formStore.changeMultiple).toBeCalledWith(
        {key: 'test-key', parentId: 32}, {isServerValue: true}
    ));
    expect(formOverlayProps.formStore.loading).toBeFalsy();
});

test('Component should update formStore on changing form key', async() => {
    const {rerender} = renderRestoreFormOverlay({formKey: 'test', open: false});
    expect(await screen.findByTestId('form-overlay')).toBeInTheDocument();

    const [initialProps] = (FormOverlay: any).mock.calls[0];
    const formStore = initialProps.formStore;

    expect(SchemaFormStoreDecorator).toBeCalledTimes(1);
    expect(SchemaFormStoreDecorator).toBeCalledWith(expect.anything(), 'test');
    expect(formStore.destroy).not.toHaveBeenCalled();

    rerender(
        <RestoreFormOverlay
            formKey="other"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            trashItemId="trash-item-123"
        />
    );

    expect(SchemaFormStoreDecorator).toBeCalledTimes(2);
    expect(SchemaFormStoreDecorator).toBeCalledWith(expect.anything(), 'other');
    expect(formStore.destroy).toHaveBeenCalled();

    const [updatedProps] = (FormOverlay: any).mock.calls[(FormOverlay: any).mock.calls.length - 1];
    expect(updatedProps.formStore.destroy).not.toHaveBeenCalled();
});

test('Component should update formStore on reopen', async() => {
    const {rerender} = renderRestoreFormOverlay({open: false});
    expect(await screen.findByTestId('form-overlay')).toBeInTheDocument();

    const [initialProps] = (FormOverlay: any).mock.calls[0];
    const formStore = initialProps.formStore;

    rerender(
        <RestoreFormOverlay
            formKey="test"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            trashItemId="trash-item-123"
        />
    );

    expect(formStore.destroy).toHaveBeenCalled();

    const [updatedProps] = (FormOverlay: any).mock.calls[(FormOverlay: any).mock.calls.length - 1];
    expect(updatedProps.formStore.destroy).not.toHaveBeenCalled();
});

test('Component should destroy formStore on unmount', async() => {
    const {unmount} = renderRestoreFormOverlay({open: false});
    expect(await screen.findByTestId('form-overlay')).toBeInTheDocument();

    const [initialProps] = (FormOverlay: any).mock.calls[0];
    const formStore = initialProps.formStore;

    unmount();
    expect(formStore.destroy).toHaveBeenCalled();
});
