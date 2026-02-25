// @flow
import mockReact from 'react';
import {act, fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable} from 'mobx';
import FormOverlay from '../FormOverlay';
import ResourceStore from '../../../stores/ResourceStore';
import MemoryFormStore from '../../../containers/Form/stores/MemoryFormStore';
import ResourceFormStore from '../../../containers/Form/stores/ResourceFormStore';
import overlayStyles from '../../../components/Overlay/overlay.scss';
import snackbarStyles from '../../../components/Snackbar/snackbar.scss';

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

function getLatestFormProps() {
    return mockFormPropsCalls[mockFormPropsCalls.length - 1];
}

function getLatestFormSubmit() {
    return mockFormSubmitFunctions[mockFormSubmitFunctions.length - 1];
}

function getConfirmButton() {
    return screen.getByRole('button', {name: 'confirm-text'});
}

function getOverlayElement() {
    return document.querySelector(`.${overlayStyles.overlay}`);
}

function getEscapedRegExp(value: string) {
    return new RegExp(value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
}

function getSnackbarCloseIcon() {
    return document.querySelector(`.${snackbarStyles.closeIcon}`);
}

function getSnackbar() {
    return document.querySelector(`.${snackbarStyles.snackbar}[role="button"]`);
}

beforeEach(() => {
    jest.clearAllMocks();
    mockFormPropsCalls.splice(0, mockFormPropsCalls.length);
    mockFormSubmitFunctions.splice(0, mockFormSubmitFunctions.length);
});

test('Component should render', () => {
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

    expect(document.body).toMatchSnapshot();
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

    expect(screen.getByText('overlay-title')).toBeInTheDocument();
    expect(getConfirmButton()).toBeDisabled();
    const overlayElement = getOverlayElement();
    if (!(overlayElement instanceof HTMLElement)) {
        throw new Error('Expected overlay element to exist');
    }
    expect(overlayElement).toHaveClass(overlayStyles.small);
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

    expect(screen.getByText('overlay-title')).toBeInTheDocument();
    expect(getConfirmButton()).toBeEnabled();
    expect(getConfirmButton()).not.toHaveClass('loading');
    const overlayElement = getOverlayElement();
    if (!(overlayElement instanceof HTMLElement)) {
        throw new Error('Expected overlay element to exist');
    }
    expect(overlayElement).not.toHaveClass(overlayStyles.small);
    expect(overlayElement).not.toHaveClass(overlayStyles.large);
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
    expect(getConfirmButton()).not.toHaveClass('loading');

    act(() => {
        resourceFormStore.saving = true;
    });
    expect(getConfirmButton()).toHaveClass('loading');
});

test('Should submit Form container when Overlay is confirmed', async() => {
    const user = userEvent.setup();
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

    await user.click(getConfirmButton());

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
    expect(screen.getByText(getEscapedRegExp('sulu_admin.form_save_server_error'))).toBeInTheDocument();
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
    expect(screen.getByText(getEscapedRegExp('URL is already assigned to another page.'))).toBeInTheDocument();
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

    expect(screen.getByText(getEscapedRegExp('sulu_admin.form_contains_invalid_values'))).toBeInTheDocument();
});

test('Should hide error when closeClick callback of Overlay snackbar is fired', async() => {
    const user = userEvent.setup();
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
    expect(screen.getByText(getEscapedRegExp('sulu_admin.form_contains_invalid_values'))).toBeInTheDocument();

    const snackbarCloseIcon = getSnackbarCloseIcon();
    if (!(snackbarCloseIcon instanceof HTMLElement)) {
        throw new Error('Expected snackbar close icon to be rendered');
    }
    await user.click(snackbarCloseIcon);
    const snackbar = getSnackbar();
    if (!(snackbar instanceof HTMLElement)) {
        throw new Error('Expected snackbar to be rendered');
    }
    fireEvent.transitionEnd(snackbar);

    expect(screen.queryByText(getEscapedRegExp('sulu_admin.form_contains_invalid_values'))).not.toBeInTheDocument();
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
    expect(screen.getByText(getEscapedRegExp('sulu_admin.form_contains_invalid_values'))).toBeInTheDocument();

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
    const snackbar = getSnackbar();
    if (!(snackbar instanceof HTMLElement)) {
        throw new Error('Expected snackbar to be rendered');
    }
    fireEvent.transitionEnd(snackbar);

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

    expect(screen.queryByText(getEscapedRegExp('sulu_admin.form_contains_invalid_values'))).not.toBeInTheDocument();
});
