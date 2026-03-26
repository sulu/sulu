// @flow
import React from 'react';
import {extendObservable as mockExtendObservable} from 'mobx';
import {render, waitFor} from '@testing-library/react';
import FormOverlay from '../FormOverlay';
import Overlay from '../../../components/Overlay';
import ResourceStore from '../../../stores/ResourceStore';
import MemoryFormStore from '../../../containers/Form/stores/MemoryFormStore';
import ResourceFormStore from '../../../containers/Form/stores/ResourceFormStore';
import Form from '../../../containers/Form';

jest.mock('../../../containers/Form', () => {
    const React = require('react');

    const submitMock = jest.fn();
    const renderMock = jest.fn(function FormMock(props, ref) {
        React.useImperativeHandle(ref, () => ({
            submit: submitMock,
        }));

        return <div>form container mock</div>;
    });
    const FormMock: any = React.forwardRef(renderMock);

    FormMock.__renderMock = renderMock;
    FormMock.__submitMock = submitMock;

    return FormMock;
});

jest.mock('../../../components/Overlay', () => jest.fn(({children}) => <div>{children}</div>));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn(
    (resourceKey, itemId) => {
        return {
            id: itemId,
        };
    }
));

jest.mock('../../../containers/Form/stores/ResourceFormStore',
    () => jest.fn(function(resourceStore, formKey, options, metadataOptions) {
        this.id = resourceStore.id;
        this.formKey = formKey;
        this.options = options;
        this.metadataOptions = metadataOptions;

        this.save = jest.fn();

        mockExtendObservable(this, {
            dirty: false,
            saving: false,
        });
    })
);

jest.mock('../../../containers/Form/stores/MemoryFormStore',
    () => jest.fn(function(data, rawSchema, jsonSchema, locale) {
        this.rawSchema = rawSchema;
        this.jsonSchema = jsonSchema;
        this.locale = locale;

        mockExtendObservable(this, {
            data,
            dirty: false,
        });
    })
);

function getLatestOverlayProps() {
    const calls = (Overlay: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestFormProps() {
    const calls = (Form: any).__renderMock.mock.calls;

    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Component should render', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);

    const {asFragment} = render(
        <FormOverlay
            confirmDisabled={false}
            confirmLoading={false}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            size="small"
            title="overlay-title"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should pass correct props to Overlay component', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);
    formStore.dirty = true;

    const closeSpy = jest.fn();

    render(
        <FormOverlay
            confirmDisabled={true}
            confirmLoading={true}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
            size="small"
            title="overlay-title"
        />
    );

    expect(getLatestOverlayProps()).toEqual(expect.objectContaining({
        confirmDisabled: true,
        confirmLoading: true,
        confirmText: 'confirm-text',
        onClose: closeSpy,
        open: true,
        size: 'small',
        title: 'overlay-title',
    }));
});

test('Should pass correct props to Overlay component when using default values', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);
    formStore.dirty = true;

    const closeSpy = jest.fn();

    render(
        <FormOverlay
            confirmText="confirm-text"
            formStore={formStore}
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
            title="overlay-title"
        />
    );

    expect(getLatestOverlayProps()).toEqual(expect.objectContaining({
        confirmDisabled: false,
        confirmLoading: false,
        confirmText: 'confirm-text',
        onClose: closeSpy,
        open: true,
        size: undefined,
        title: 'overlay-title',
    }));
});

test('Should pass correct props to Form component', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);

    render(
        <FormOverlay
            confirmDisabled={false}
            confirmLoading={false}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            size="small"
            title="overlay-title"
        />
    );

    expect(getLatestFormProps()).toEqual(expect.objectContaining({
        store: formStore,
    }));
});

test('Should display confirm button as loading if FormStore is saving', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');

    render(
        <FormOverlay
            confirmDisabled={false}
            confirmLoading={false}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            size="small"
            title="overlay-title"
        />
    );

    // $FlowFixMe
    formStore.saving = false;
    expect(getLatestOverlayProps().confirmLoading).toEqual(false);

    // $FlowFixMe
    formStore.saving = true;
    await waitFor(() => {
        expect(getLatestOverlayProps().confirmLoading).toEqual(true);
    });
});

test('Should submit Form container when Overlay is confirmed', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);

    render(
        <FormOverlay
            confirmDisabled={false}
            confirmLoading={false}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            size="small"
            title="overlay-title"
        />
    );

    getLatestOverlayProps().onConfirm();

    expect((Form: any).__submitMock).toBeCalled();
});

test('Should save ResourceFormStore and call onConfirm callback on submit of Form', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    render(
        <FormOverlay
            confirmDisabled={false}
            confirmLoading={false}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            size="small"
            title="overlay-title"
        />
    );

    const savePromise = Promise.resolve();
    formStore.save.mockReturnValueOnce(savePromise);

    getLatestFormProps().onSubmit();

    await savePromise;
    expect(formStore.save).toBeCalled();
    expect(confirmSpy).toBeCalled();
});

test('Should call onConfirm callback directly in case of MemoryFormStore on submit of Form', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);
    const confirmSpy = jest.fn();

    render(
        <FormOverlay
            confirmDisabled={false}
            confirmLoading={false}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            size="small"
            title="overlay-title"
        />
    );

    getLatestFormProps().onSubmit();

    expect(confirmSpy).toBeCalled();
});

test('Should display Snackbar with generic message if an error happens while saving ResourceFormStore', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    render(
        <FormOverlay
            confirmDisabled={false}
            confirmLoading={false}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            size="small"
            title="overlay-title"
        />
    );

    const savePromise = Promise.reject('error');
    formStore.save.mockReturnValueOnce(savePromise);

    getLatestFormProps().onSubmit();

    await waitFor(() => {
        expect(getLatestOverlayProps().snackbarMessage).toEqual('sulu_admin.form_save_server_error');
    });
    expect(formStore.save).toBeCalled();
    expect(confirmSpy).not.toBeCalled();
});

test('Should display Snackbar with message from server if an error happens while saving ResourceFormStore', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    render(
        <FormOverlay
            confirmDisabled={false}
            confirmLoading={false}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            size="small"
            title="overlay-title"
        />
    );

    const savePromise = Promise.reject({code: 100, detail: 'URL is already assigned to another page.'});
    formStore.save.mockReturnValueOnce(savePromise);

    getLatestFormProps().onSubmit();

    await waitFor(() => {
        expect(getLatestOverlayProps().snackbarMessage).toEqual('URL is already assigned to another page.');
    });
    expect(formStore.save).toBeCalled();
    expect(confirmSpy).not.toBeCalled();
});

test('Should display Snackbar if a form is not valid', () => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    render(
        <FormOverlay
            confirmDisabled={false}
            confirmLoading={false}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            size="small"
            title="overlay-title"
        />
    );

    getLatestFormProps().onError();

    expect(getLatestOverlayProps().snackbarMessage).toEqual('sulu_admin.form_contains_invalid_values');
});

test('Should hide Snackbar when closeClick callback of Snackbar is fired', () => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    render(
        <FormOverlay
            confirmDisabled={false}
            confirmLoading={false}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            size="small"
            title="overlay-title"
        />
    );

    getLatestFormProps().onError();
    expect(getLatestOverlayProps().snackbarMessage).toEqual('sulu_admin.form_contains_invalid_values');

    getLatestOverlayProps().onSnackbarCloseClick();
    expect(getLatestOverlayProps().snackbarMessage).toBeUndefined();
});

test('Should clear old errors if Overlay is opened a second time', () => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    const {rerender} = render(
        <FormOverlay
            confirmDisabled={false}
            confirmLoading={false}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            size="small"
            title="overlay-title"
        />
    );

    getLatestFormProps().onError();
    expect(getLatestOverlayProps().snackbarMessage).toEqual('sulu_admin.form_contains_invalid_values');

    rerender(
        <FormOverlay
            confirmDisabled={false}
            confirmLoading={false}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={false}
            size="small"
            title="overlay-title"
        />
    );
    rerender(
        <FormOverlay
            confirmDisabled={false}
            confirmLoading={false}
            confirmText="confirm-text"
            formStore={formStore}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            size="small"
            title="overlay-title"
        />
    );

    expect(getLatestOverlayProps().snackbarMessage).toBeUndefined();
});
