// @flow
import React from 'react';
import {act, render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable} from 'mobx';
import FormOverlay from '../FormOverlay';
import ResourceStore from '../../../stores/ResourceStore';
import MemoryFormStore from '../../../containers/Form/stores/MemoryFormStore';
import ResourceFormStore from '../../../containers/Form/stores/ResourceFormStore';

let mockOverlayProps: Object = {};
let mockFormProps: Object = {};
let mockFormInstance: any;

jest.mock('../../../containers/Form', () => {
    const ReactMock = require('react');

    return class FormMock extends ReactMock.Component<*> {
        submit = jest.fn();

        componentDidMount() {
            mockFormInstance = this;
        }

        componentDidUpdate() {
            mockFormInstance = this;
        }

        render() {
            mockFormProps = this.props;

            return ReactMock.createElement(
                'div',
                {'data-testid': 'form'},
                'form container mock',
                ReactMock.createElement(
                    'button',
                    {
                        'aria-label': 'form-submit',
                        onClick: this.props.onSubmit,
                        type: 'button',
                    },
                    'Submit'
                ),
                ReactMock.createElement(
                    'button',
                    {
                        'aria-label': 'form-error',
                        onClick: this.props.onError,
                        type: 'button',
                    },
                    'Error'
                )
            );
        }
    };
});

jest.mock('../../../components/Overlay', () => jest.fn((props) => {
    const ReactMock = require('react');

    mockOverlayProps = props;

    if (!props.open) {
        return null;
    }

    return ReactMock.createElement(
        'section',
        {
            'data-confirm-disabled': props.confirmDisabled ? 'true' : 'false',
            'data-confirm-loading': props.confirmLoading ? 'true' : 'false',
            'data-size': props.size,
            'data-testid': 'overlay',
        },
        ReactMock.createElement('h2', {}, props.title),
        props.children,
        ReactMock.createElement(
            'button',
            {
                'aria-label': 'overlay-confirm',
                disabled: props.confirmDisabled,
                onClick: props.onConfirm,
                type: 'button',
            },
            props.confirmText
        ),
        props.snackbarMessage && ReactMock.createElement(
            'div',
            {'data-testid': 'snackbar'},
            props.snackbarMessage,
            ReactMock.createElement(
                'button',
                {
                    'aria-label': 'snackbar-close',
                    onClick: props.onSnackbarCloseClick,
                    type: 'button',
                },
                'Close snackbar'
            )
        )
    );
}));

jest.mock('../../../utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn((resourceKey, itemId) => ({
    id: itemId,
})));

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

beforeEach(() => {
    jest.clearAllMocks();
    mockOverlayProps = {};
    mockFormProps = {};
    mockFormInstance = undefined;
});

function renderFormOverlay(props: Object = {}) {
    const formStore = props.formStore || new MemoryFormStore({}, {}, undefined, undefined);

    return {
        formStore,
        ...render(
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
                {...props}
            />
        ),
    };
}

test('Component should render', () => {
    renderFormOverlay();

    expect(screen.getByText('overlay-title')).toBeInTheDocument();
    expect(screen.getByTestId('form')).toHaveTextContent('form container mock');
    expect(screen.getByText('confirm-text')).toBeInTheDocument();
});

test('Should pass correct props to Overlay component', () => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);
    formStore.dirty = true;

    const closeSpy = jest.fn();

    renderFormOverlay({
        confirmDisabled: true,
        confirmLoading: true,
        formStore,
        onClose: closeSpy,
    });

    expect(mockOverlayProps).toEqual(expect.objectContaining({
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

    expect(mockOverlayProps).toEqual(expect.objectContaining({
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

    renderFormOverlay({formStore});

    expect(mockFormProps).toEqual(expect.objectContaining({
        store: formStore,
    }));
});

test('Should display confirm button as loading if FormStore is saving', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');

    renderFormOverlay({formStore});

    (formStore: any).saving = false;
    expect(mockOverlayProps.confirmLoading).toEqual(false);

    act(() => {
        (formStore: any).saving = true;
    });

    await waitFor(() => expect(mockOverlayProps.confirmLoading).toEqual(true));
});

test('Should submit Form container when Overlay is confirmed', async() => {
    renderFormOverlay();

    expect(mockFormInstance.submit).not.toHaveBeenCalled();

    await userEvent.click(screen.getByLabelText('overlay-confirm'));

    expect(mockFormInstance.submit).toHaveBeenCalled();
});

test('Should save ResourceFormStore and call onConfirm callback on submit of Form', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();
    const savePromise = Promise.resolve();
    formStore.save.mockReturnValueOnce(savePromise);

    renderFormOverlay({
        formStore,
        onConfirm: confirmSpy,
    });

    await userEvent.click(screen.getByLabelText('form-submit'));
    await savePromise;

    expect(formStore.save).toHaveBeenCalled();
    expect(confirmSpy).toHaveBeenCalled();
});

test('Should call onConfirm callback directly in case of MemoryFormStore on submit of Form', async() => {
    const formStore = new MemoryFormStore({}, {}, undefined, undefined);
    const confirmSpy = jest.fn();

    renderFormOverlay({
        formStore,
        onConfirm: confirmSpy,
    });

    await userEvent.click(screen.getByLabelText('form-submit'));

    expect(confirmSpy).toHaveBeenCalled();
});

test('Should display Snackbar with generic message if an error happens while saving ResourceFormStore', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();
    formStore.save.mockImplementationOnce(() => Promise.reject('error'));

    renderFormOverlay({
        formStore,
        onConfirm: confirmSpy,
    });

    await userEvent.click(screen.getByLabelText('form-submit'));

    expect(formStore.save).toHaveBeenCalled();
    expect(confirmSpy).not.toHaveBeenCalled();
    expect(await screen.findByTestId('snackbar')).toHaveTextContent('sulu_admin.form_save_server_error');
});

test('Should display Snackbar with message from server if an error happens while saving ResourceFormStore', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();
    formStore.save.mockImplementationOnce(() => Promise.reject({
        code: 100,
        detail: 'URL is already assigned to another page.',
    }));

    renderFormOverlay({
        formStore,
        onConfirm: confirmSpy,
    });

    await userEvent.click(screen.getByLabelText('form-submit'));

    expect(formStore.save).toHaveBeenCalled();
    expect(confirmSpy).not.toHaveBeenCalled();
    expect(await screen.findByTestId('snackbar')).toHaveTextContent('URL is already assigned to another page.');
});

test('Should display Snackbar if a form is not valid', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    renderFormOverlay({
        formStore,
        onConfirm: confirmSpy,
    });

    await userEvent.click(screen.getByLabelText('form-error'));

    expect(screen.getByTestId('snackbar')).toHaveTextContent('sulu_admin.form_contains_invalid_values');
});

test('Should hide Snackbar when closeClick callback of Snackbar is fired', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    renderFormOverlay({
        formStore,
        onConfirm: confirmSpy,
    });

    await userEvent.click(screen.getByLabelText('form-error'));
    expect(screen.getByTestId('snackbar')).toBeInTheDocument();

    await userEvent.click(screen.getByLabelText('snackbar-close'));

    expect(screen.queryByTestId('snackbar')).not.toBeInTheDocument();
});

test('Should clear old errors if Overlay is opened a second time', async() => {
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();

    const {rerender} = renderFormOverlay({
        formStore,
        onConfirm: confirmSpy,
    });

    await userEvent.click(screen.getByLabelText('form-error'));
    expect(screen.getByTestId('snackbar')).toBeInTheDocument();

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

    expect(screen.queryByTestId('snackbar')).not.toBeInTheDocument();
});
