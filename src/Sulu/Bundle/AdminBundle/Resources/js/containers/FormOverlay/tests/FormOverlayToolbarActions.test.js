// @flow
import mockReact from 'react';
import {fireEvent, render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable} from 'mobx';
import FormOverlay from '../FormOverlay';
import ResourceStore from '../../../stores/ResourceStore';
import MemoryFormStore from '../../../containers/Form/stores/MemoryFormStore';
import ResourceFormStore from '../../../containers/Form/stores/ResourceFormStore';

const React = mockReact;

// the mock exposes the callbacks the real Form triggers, so a test can reach them by clicking
jest.mock('../../../containers/Form', () => class FormMock extends mockReact.Component<*> {
    submit = jest.fn((options) => this.props.onSubmit(options));

    handleSubmit = () => this.props.onSubmit();

    handleError = () => this.props.onError();

    render() {
        return (
            <div data-router={this.props.router ? 'yes' : 'no'} data-testid="form-container">
                form container mock
                <button onClick={this.handleSubmit} type="button">trigger submit</button>
                <button onClick={this.handleError} type="button">trigger error</button>
            </div>
        );
    }
});

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../services/initializer', () => ({
    initializedTranslationsLocale: true,
}));

jest.mock('debounce', () => jest.fn((callback) => callback));

beforeEach(() => {
    window.ResizeObserver = jest.fn(function() {
        this.observe = jest.fn();
        this.disconnect = jest.fn();
    });
});

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

function renderFormOverlay(props: Object = {}) {
    return render(
        <FormOverlay
            confirmText="confirm-text"
            formStore={new MemoryFormStore({}, {}, undefined, undefined)}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            title="overlay-title"
            {...(props: any)}
        />
    );
}

// a stand-in for a registered form toolbar action, driven through its rendered toolbar button
function createToolbarAction(label: string, onClick: () => void) {
    return {
        destroy: jest.fn(),
        getNode: jest.fn(() => null),
        getToolbarItemConfig: jest.fn(() => ({label, onClick, type: 'button'})),
    };
}

test('Should render a toolbar and hide the footer confirm when toolbar actions are given', () => {
    const action = createToolbarAction('Save', jest.fn());

    renderFormOverlay({
        toolbarActionsProvider: () => [action],
        toolbarStoreKey: 'form-overlay-toolbar-test',
    });

    expect(screen.getByRole('button', {name: 'Save'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'confirm-text'})).not.toBeInTheDocument();
});

test('Should keep the footer confirm and render no toolbar without toolbar actions', () => {
    renderFormOverlay();

    expect(screen.getByRole('button', {name: 'confirm-text'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'Save'})).not.toBeInTheDocument();
});

test('Should mount with toolbar actions while the overlay is still closed', () => {
    renderFormOverlay({
        open: false,
        toolbarActionsProvider: () => [],
        toolbarStoreKey: 'form-overlay-closed-test',
    });

    expect(screen.queryByRole('heading', {name: 'overlay-title'})).not.toBeInTheDocument();
});

test('Should show a success snackbar instead of confirming when toolbar actions are given', async() => {
    const user = userEvent.setup();
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    const confirmSpy = jest.fn();
    formStore.save.mockReturnValue(Promise.resolve());

    renderFormOverlay({
        formStore,
        onConfirm: confirmSpy,
        toolbarActionsProvider: (form) => [createToolbarAction('Save', () => form.submit())],
        toolbarStoreKey: 'form-overlay-save-test',
    });

    await user.click(screen.getByRole('button', {name: 'Save'}));

    expect(await screen.findByRole('button', {name: /sulu_admin.success/i})).toBeInTheDocument();
    expect(confirmSpy).not.toHaveBeenCalled();
});

test('Should let a pending error take precedence over a success message', async() => {
    const user = userEvent.setup();

    renderFormOverlay({
        toolbarActionsProvider: (form) => [
            createToolbarAction('Succeed', () => form.showSuccessSnackbar()),
            createToolbarAction('Fail', () => { form.errors.push('Boom'); }),
        ],
        toolbarStoreKey: 'form-overlay-precedence-test',
    });

    await user.click(screen.getByRole('button', {name: 'Succeed'}));
    expect(screen.getByRole('button', {name: /sulu_admin.success/i})).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'Fail'}));

    expect(screen.getByText(/Boom/)).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /sulu_admin.success/i})).not.toBeInTheDocument();
});

test('Should pass submit options through to the form store save', async() => {
    const user = userEvent.setup();
    const formStore = new ResourceFormStore(new ResourceStore('test'), 'test');
    formStore.save.mockReturnValue(Promise.resolve());

    renderFormOverlay({
        formStore,
        toolbarActionsProvider: (form) => [
            createToolbarAction('Publish', () => form.submit({action: 'publish'})),
        ],
        toolbarStoreKey: 'form-overlay-options-test',
    });

    await user.click(screen.getByRole('button', {name: 'Publish'}));

    expect(formStore.save).toHaveBeenCalledWith({action: 'publish'});
});

test('Should render the message of an error pushed as an object by a toolbar action', async() => {
    const user = userEvent.setup();

    renderFormOverlay({
        toolbarActionsProvider: (form) => [
            createToolbarAction('Fail', () => {
                form.errors.push({message: 'Limit reached', title: 'Account limit'});
            }),
        ],
        toolbarStoreKey: 'form-overlay-object-error-test',
    });

    await user.click(screen.getByRole('button', {name: 'Fail'}));

    expect(screen.getByText(/Limit reached/)).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /sulu_admin.error/i})).toBeInTheDocument();
});

test('Should show a warning pushed by a toolbar action when no error is pending', async() => {
    const user = userEvent.setup();

    renderFormOverlay({
        toolbarActionsProvider: (form) => [
            createToolbarAction('Warn', () => {
                form.warnings.push({message: 'Request failed', title: 'Try again'});
            }),
            createToolbarAction('Fail', () => { form.errors.push('Boom'); }),
        ],
        toolbarStoreKey: 'form-overlay-warning-test',
    });

    await user.click(screen.getByRole('button', {name: 'Warn'}));
    expect(screen.getByText(/Request failed/)).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /sulu_admin.warning/i})).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'Fail'}));

    expect(screen.getByText(/Boom/)).toBeInTheDocument();
    expect(screen.queryByText(/Request failed/)).not.toBeInTheDocument();
});

test('Should dismiss the success snackbar when its close button is clicked', async() => {
    const user = userEvent.setup();

    renderFormOverlay({
        toolbarActionsProvider: (form) => [
            createToolbarAction('Succeed', () => form.showSuccessSnackbar()),
        ],
        toolbarStoreKey: 'form-overlay-dismiss-test',
    });

    await user.click(screen.getByRole('button', {name: 'Succeed'}));
    expect(screen.getByRole('button', {name: /sulu_admin.success/i})).toBeInTheDocument();

    const successSnackbar = screen.getByRole('button', {name: /sulu_admin.success/i});
    await user.click(within(successSnackbar).getByRole('button', {name: 'su-times'}));
    fireEvent.transitionEnd(successSnackbar);

    expect(screen.queryByRole('button', {name: /sulu_admin.success/i})).not.toBeInTheDocument();
});
