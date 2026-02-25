// @flow
import mockReact from 'react';
import {act, render} from '@testing-library/react';
import {extendObservable as mockExtendObservable} from 'mobx';
import FormOverlay from '../FormOverlay';
import Overlay from '../../../components/Overlay';
import ResourceStore from '../../../stores/ResourceStore';
import MemoryFormStore from '../../../containers/Form/stores/MemoryFormStore';
import ResourceFormStore from '../../../containers/Form/stores/ResourceFormStore';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

const React = mockReact;
const mockFormPropsCalls = [];
const mockFormSubmitFunctions = [];

jest.mock('../../../containers/Form', () => {
    const React = require('react');

    return React.forwardRef(function FormMock(props, ref) {
        const submit = React.useRef(jest.fn());
        React.useImperativeHandle(ref, () => ({submit: submit.current}));

        mockFormPropsCalls.push(props);
        mockFormSubmitFunctions.push(submit.current);

        return <div>form container mock</div>;
    });
});

jest.mock('../../../components/Overlay', () => jest.fn(function OverlayMock({children}) {
    return <div>{children}</div>;
}));

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
    return getLatestMockProps((Overlay: any));
}

function getLatestFormProps() {
    return mockFormPropsCalls[mockFormPropsCalls.length - 1];
}

function getLatestFormSubmit() {
    return mockFormSubmitFunctions[mockFormSubmitFunctions.length - 1];
}

beforeEach(() => {
    jest.clearAllMocks();
    mockFormPropsCalls.splice(0, mockFormPropsCalls.length);
    mockFormSubmitFunctions.splice(0, mockFormSubmitFunctions.length);
});

test('Component should render', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);

    const {asFragment} = render(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    expect(asFragment()).toMatchSnapshot();
});

test('Should pass correct props to Overlay component', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);
    formStore.dirty = true;

    const closeSpy = jest.fn();

    render(<FormOverlay
        confirmDisabled={true}
        confirmLoading={true}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={closeSpy}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    const overlayProps = getLatestOverlayProps();

    expect(overlayProps).toEqual(expect.objectContaining({
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

    render(<FormOverlay
        confirmText="confirm-text"
        formStore={formStore}
        onClose={closeSpy}
        onConfirm={jest.fn()}
        open={true}
        title="overlay-title"
    />);

    const overlayProps = getLatestOverlayProps();

    expect(overlayProps).toEqual(expect.objectContaining({
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

    render(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    expect(getLatestFormProps()).toEqual(expect.objectContaining({
        store: formStore,
    }));
});

test('Should display confirm button as loading if FormStore is saving', () => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');

    render(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    const resourceFormStore: any = formStore;
    resourceFormStore.saving = false;
    expect(getLatestOverlayProps().confirmLoading).toEqual(false);

    act(() => {
        resourceFormStore.saving = true;
    });
    expect(getLatestOverlayProps().confirmLoading).toEqual(true);
});

test('Should submit Form container when Overlay is confirmed', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);

    render(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    const submitSpy = getLatestFormSubmit();

    act(() => {
        getLatestOverlayProps().onConfirm();
    });

    expect(submitSpy).toBeCalled();
});

test('Should save ResourceFormStore and call onConfirm callback on submit of Form', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    render(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={confirmSpy}
        open={true}
        size="small"
        title="overlay-title"
    />);

    const savePromise = Promise.resolve();
    formStore.save.mockReturnValueOnce(savePromise);

    await act(async() => {
        getLatestFormProps().onSubmit();
        await savePromise;
    });

    expect(formStore.save).toBeCalled();
    expect(confirmSpy).toBeCalled();
});

test('Should call onConfirm callback directly in case of MemoryFormStore on submit of Form', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);
    const confirmSpy = jest.fn();

    render(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={confirmSpy}
        open={true}
        size="small"
        title="overlay-title"
    />);

    act(() => {
        getLatestFormProps().onSubmit();
    });

    expect(confirmSpy).toBeCalled();
});

test('Should display generic error message if an error happens while saving ResourceFormStore', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    render(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={confirmSpy}
        open={true}
        size="small"
        title="overlay-title"
    />);

    const savePromise = Promise.reject('error');
    formStore.save.mockReturnValueOnce(savePromise);

    await act(async() => {
        getLatestFormProps().onSubmit();
        await savePromise.catch(() => undefined);
    });

    expect(formStore.save).toBeCalled();
    expect(confirmSpy).not.toBeCalled();
    expect(getLatestOverlayProps().snackbarMessage).toEqual('sulu_admin.form_save_server_error');
});

test('Should display error detail if an error happens while saving ResourceFormStore', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    render(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={confirmSpy}
        open={true}
        size="small"
        title="overlay-title"
    />);

    const savePromise = Promise.reject({code: 100, detail: 'URL is already assigned to another page.'});
    formStore.save.mockReturnValueOnce(savePromise);

    await act(async() => {
        getLatestFormProps().onSubmit();
        await savePromise.catch(() => undefined);
    });

    expect(formStore.save).toBeCalled();
    expect(confirmSpy).not.toBeCalled();
    expect(getLatestOverlayProps().snackbarMessage).toEqual('URL is already assigned to another page.');
});

test('Should display error if a form is not valid', () => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');

    render(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    act(() => {
        getLatestFormProps().onError();
    });

    expect(getLatestOverlayProps().snackbarMessage).toEqual('sulu_admin.form_contains_invalid_values');
});

test('Should hide error when closeClick callback of Overlay snackbar is fired', () => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');

    render(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    act(() => {
        getLatestFormProps().onError();
    });
    expect(getLatestOverlayProps().snackbarMessage).toEqual('sulu_admin.form_contains_invalid_values');

    act(() => {
        getLatestOverlayProps().onSnackbarCloseClick();
    });
    expect(getLatestOverlayProps().snackbarMessage).toBeUndefined();
});

test('Should clear old errors if Overlay is opened a second time', () => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');

    const {rerender} = render(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    act(() => {
        getLatestFormProps().onError();
    });
    expect(getLatestOverlayProps().snackbarMessage).toEqual('sulu_admin.form_contains_invalid_values');

    rerender(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={false}
        size="small"
        title="overlay-title"
    />);

    rerender(<FormOverlay
        confirmDisabled={false}
        confirmLoading={false}
        confirmText="confirm-text"
        formStore={formStore}
        onClose={jest.fn()}
        onConfirm={jest.fn()}
        open={true}
        size="small"
        title="overlay-title"
    />);

    expect(getLatestOverlayProps().snackbarMessage).toBeUndefined();
});
