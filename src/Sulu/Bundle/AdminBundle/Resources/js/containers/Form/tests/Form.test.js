// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import log from 'loglevel';
import Form from '../Form';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../stores/ResourceFormStore';

const mockReact = require('react');
let mockRendererProps: Object = {};
let mockGhostDialogConfirmOptions: Object = {};

jest.mock('loglevel', () => ({
    warn: jest.fn(),
    debug: jest.fn(),
}));

jest.mock('../../../services/Router/Router', () => jest.fn());

jest.mock('../../../utils/Translator');

jest.mock('../Renderer', () => jest.fn((props) => {
    mockRendererProps = props;

    return mockReact.createElement(
        'div',
        {
            'data-show-all-errors': props.showAllErrors ? 'true' : 'false',
            'data-testid': 'renderer',
        },
        mockReact.createElement(
            'button',
            {
                'aria-label': 'renderer-finish',
                onClick: () => props.onFieldFinish('/article', '/article'),
                type: 'button',
            },
            'Finish'
        ),
        mockReact.createElement(
            'button',
            {
                'aria-label': 'renderer-change',
                onClick: () => props.onChange('field', 'value', {isDefaultValue: true}),
                type: 'button',
            },
            'Change'
        )
    );
}));

jest.mock('../GhostDialog', () => jest.fn((props) => {
    return mockReact.createElement(
        'div',
        {
            'data-open': props.open ? 'true' : 'false',
            'data-testid': 'ghost-dialog',
        },
        mockReact.createElement(
            'button',
            {
                'aria-label': 'ghost-confirm',
                onClick: () => props.onConfirm('en', mockGhostDialogConfirmOptions),
                type: 'button',
            },
            'Confirm'
        ),
        mockReact.createElement(
            'button',
            {
                'aria-label': 'ghost-cancel',
                onClick: props.onCancel,
                type: 'button',
            },
            'Cancel'
        )
    );
}));

jest.mock('../MissingTypeDialog', () => jest.fn((props) => {
    return mockReact.createElement(
        'div',
        {
            'data-open': props.open ? 'true' : 'false',
            'data-testid': 'missing-type-dialog',
        },
        mockReact.createElement(
            'button',
            {
                'aria-label': 'missing-type-confirm',
                onClick: () => props.onConfirm('default'),
                type: 'button',
            },
            'Confirm'
        ),
        mockReact.createElement(
            'button',
            {
                'aria-label': 'missing-type-cancel',
                onClick: props.onCancel,
                type: 'button',
            },
            'Cancel'
        )
    );
}));

jest.mock('../stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.id = resourceStore.id;
    this.resourceKey = resourceStore.resourceKey;
    this.data = resourceStore.data;
    this.locale = resourceStore.observableOptions.locale;
    this.loading = resourceStore.loading;
    this.validate = jest.fn().mockReturnValue(true);
    this.schema = {};
    this.set = jest.fn();
    this.change = jest.fn();
    this.finishField = jest.fn();
    this.isFieldModified = jest.fn();
    this.copyFromLocale = jest.fn();
    this.getValueByPath = jest.fn();
    this.getSchemaEntryByPath = jest.fn().mockReturnValue({types: {default: {form: {}}}});
    this.types = {};
    this.changeType = jest.fn();
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions = {}) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.data = {};
    this.observableOptions = observableOptions;
    this.loading = false;
}));

function renderForm(props: Object = {}, store?: Object) {
    const formRef = React.createRef<any>();
    const formStore = store || new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    const view = render(
        <Form
            onSubmit={jest.fn()}
            ref={formRef}
            store={formStore}
            {...props}
        />
    );

    if (!formRef.current) {
        throw new Error('Form ref was not assigned.');
    }

    return {
        form: formRef.current,
        store: formStore,
        ...view,
    };
}

beforeEach(() => {
    jest.clearAllMocks();
    mockRendererProps = {};
    mockGhostDialogConfirmOptions = {};
});

test('Should render form using renderer', () => {
    renderForm();

    expect(screen.getByTestId('renderer')).toBeInTheDocument();
});

test('Render permission hint if permissions are missing', () => {
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    (store: any).forbidden = true;

    renderForm({}, store);

    expect(screen.getByText('sulu_admin.no_permissions')).toBeInTheDocument();
    expect(screen.queryByTestId('renderer')).not.toBeInTheDocument();
});

test('Should call onSubmit callback', () => {
    const errorSpy = jest.fn();
    const submitSpy = jest.fn();
    const {form} = renderForm({
        onError: errorSpy,
        onSubmit: submitSpy,
    });

    act(() => {
        form.submit();
    });

    expect(errorSpy).not.toHaveBeenCalled();
    expect(submitSpy).toHaveBeenCalled();
});

test.each([
    ['draft'],
    ['publish'],
])('Call saveHandlers with the action "%s" as argument when form is submitted', (action) => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();
    const submitPromise = Promise.resolve();
    const submitSpy = jest.fn().mockReturnValue(submitPromise);
    const {form} = renderForm({
        onSubmit: submitSpy,
    });

    form.formInspector.addSaveHandler(handler1);
    form.formInspector.addSaveHandler(handler2);

    form.submit(action);

    return submitPromise.then(() => {
        expect(handler1).toHaveBeenCalledWith(action);
        expect(handler2).toHaveBeenCalledWith(action);
        expect(log.warn).toHaveBeenCalled();
    });
});

test.each([
    [undefined],
    [{action: 'draft'}],
    [{inherit: true}],
])('Call saveHandlers with the action "%s" as argument when form is submitted', (action) => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();
    const submitPromise = Promise.resolve();
    const submitSpy = jest.fn().mockReturnValue(submitPromise);
    const {form} = renderForm({
        onSubmit: submitSpy,
    });

    form.formInspector.addSaveHandler(handler1);
    form.formInspector.addSaveHandler(handler2);

    form.submit(action);

    return submitPromise.then(() => {
        expect(handler1).toHaveBeenCalledWith(action);
        expect(handler2).toHaveBeenCalledWith(action);
        expect(log.warn).not.toHaveBeenCalled();
    });
});

test('Should call onError callback', () => {
    const errorSpy = jest.fn();
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    store.validate.mockReturnValue(false);
    store.errors = {error1: {}};
    const {form} = renderForm({
        onError: errorSpy,
        onSubmit: submitSpy,
    }, store);

    act(() => {
        form.submit();
    });

    expect(errorSpy).toHaveBeenCalledWith(store.errors);
    expect(submitSpy).not.toHaveBeenCalled();
});

test('Should work when errors occurs but no onError callback is given', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    store.validate.mockReturnValue(false);
    store.errors = {error1: {}};
    const {form} = renderForm({
        onSubmit: submitSpy,
    }, store);

    act(() => {
        form.submit();
    });

    expect(submitSpy).not.toHaveBeenCalled();
});

test('Should validate form when a field has finished being edited', async() => {
    const user = userEvent.setup();
    const {store} = renderForm();

    await user.click(screen.getByLabelText('renderer-finish'));

    expect(store.validate).toHaveBeenCalledWith();
});

test('Should validate form before calling finish handlers when a field has finished being edited', async() => {
    const user = userEvent.setup();
    const handler1 = jest.fn(() => {
        expect(validateCalled).toEqual(true);
    });
    const {form, store} = renderForm();

    form.formInspector.addFinishFieldHandler(handler1);

    let validateCalled = false;
    store.validate.mockImplementation(() => validateCalled = true);
    await user.click(screen.getByLabelText('renderer-finish'));
});

test('Call finish handlers with dataPath and schemaPath when a field has finished being edited', async() => {
    const user = userEvent.setup();
    const handler1 = jest.fn();
    const handler2 = jest.fn();
    const {form} = renderForm();

    form.formInspector.addFinishFieldHandler(handler1);
    form.formInspector.addFinishFieldHandler(handler2);

    await user.click(screen.getByLabelText('renderer-finish'));

    expect(handler1).toHaveBeenLastCalledWith('/article', '/article');
    expect(handler2).toHaveBeenLastCalledWith('/article', '/article');
});

test('Should pass data, onSuccess, router and schema to Renderer', () => {
    const router = new Router();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    const successSpy = jest.fn();

    store.schema = {};
    store.data.title = 'Title';
    store.data.description = 'Description';
    renderForm({
        onSuccess: successSpy,
        router,
    }, store);

    expect(mockRendererProps).toEqual(expect.objectContaining({
        data: store.data,
        onSuccess: successSpy,
        router,
        schema: store.schema,
        value: store.data,
    }));

    const formInspector = mockRendererProps.formInspector;
    expect(formInspector.resourceKey).toEqual('snippet');
    expect(formInspector.id).toEqual('1');
});

test('Should pass showAllErrors flag to Renderer when form has been submitted', () => {
    const {form} = renderForm();

    expect(screen.getByTestId('renderer')).toHaveAttribute('data-show-all-errors', 'false');

    act(() => {
        form.submit();
    });

    expect(screen.getByTestId('renderer')).toHaveAttribute('data-show-all-errors', 'true');
});

test('Should change data on store when changed', async() => {
    const user = userEvent.setup();
    const {store} = renderForm();

    await user.click(screen.getByLabelText('renderer-change'));

    expect(store.change).toHaveBeenCalledWith('field', 'value', {isDefaultValue: true});
});

test('Should change data on store without sections', () => {
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    renderForm({}, store);

    mockRendererProps.onChange('item11', 'value!');

    expect(store.change).toHaveBeenCalledWith('item11', 'value!', undefined);
});

test('Should show a GhostDialog if the current locale is not translated', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    renderForm({}, formStore);

    expect(screen.getByTestId('ghost-dialog')).toHaveAttribute('data-open', 'true');
});

test('Should not show a GhostDialog if the current locale is translated', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    renderForm({}, formStore);

    expect(screen.getByTestId('ghost-dialog')).toHaveAttribute('data-open', 'false');
});

test('Should show a GhostDialog after the locale has been switched to a non-translated one', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    renderForm({}, formStore);

    expect(screen.getByTestId('ghost-dialog')).toHaveAttribute('data-open', 'false');

    const {locale} = resourceStore.observableOptions;
    if (!locale) {
        throw new Error('The "locale" must be set!');
    }

    act(() => {
        locale.set('de');
    });

    expect(screen.getByTestId('ghost-dialog')).toHaveAttribute('data-open', 'true');
});

test('Should not show a GhostDialog if the entity does not exist yet', () => {
    const resourceStore = new ResourceStore('snippet', undefined, {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    renderForm({}, formStore);

    expect(screen.queryByTestId('ghost-dialog')).not.toBeInTheDocument();
});

test('Should not show a GhostDialog if the entity is not translatable', () => {
    const resourceStore = new ResourceStore('snippet', '1');
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    renderForm({}, formStore);

    expect(screen.queryByTestId('ghost-dialog')).not.toBeInTheDocument();
});

test('Should show a GhostDialog and copy the content if the confirm button is clicked', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    resourceStore.loading = false;
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    renderForm({}, formStore);

    expect(screen.getByTestId('ghost-dialog')).toHaveAttribute('data-open', 'true');
    await user.click(screen.getByLabelText('ghost-confirm'));

    expect(screen.getByTestId('ghost-dialog')).toHaveAttribute('data-open', 'false');
    expect(formStore.copyFromLocale).toHaveBeenCalledWith('en', {});
});

test('Should show a GhostDialog and copy the content if the confirm button is clicked (with additional fields)', async() => { // eslint-disable-line max-len
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    resourceStore.loading = false;
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    mockGhostDialogConfirmOptions = {
        title: 'Test 123',
    };
    renderForm({}, formStore);

    expect(screen.getByTestId('ghost-dialog')).toHaveAttribute('data-open', 'true');
    await user.click(screen.getByLabelText('ghost-confirm'));

    expect(screen.getByTestId('ghost-dialog')).toHaveAttribute('data-open', 'false');
    expect(formStore.copyFromLocale).toHaveBeenCalledWith('en', {
        title: 'Test 123',
    });
});

test('Should show a GhostDialog and do nothing if the cancel button is clicked', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    renderForm({}, formStore);

    expect(screen.getByTestId('ghost-dialog')).toHaveAttribute('data-open', 'true');
    await user.click(screen.getByLabelText('ghost-cancel'));
    expect(screen.getByTestId('ghost-dialog')).toHaveAttribute('data-open', 'false');

    expect(formStore.copyFromLocale).not.toHaveBeenCalled();
});

test('Should not show a GhostDialog if the resourceStore is currently loading', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    resourceStore.loading = true;
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const {form} = renderForm({}, formStore);

    expect(form.displayGhostDialog).toEqual(false);
});

test('Should set the type of the formStore to selected value in MissingTypeDialog', async() => {
    const user = userEvent.setup();
    const onMissingTypeCancelSpy = jest.fn();
    const resourceStore = new ResourceStore('snippet', '1');
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    (formStore: any).hasInvalidType = true;
    formStore.types = {
        default: {key: 'default', title: 'Default'},
    };

    renderForm({
        onMissingTypeCancel: onMissingTypeCancelSpy,
    }, formStore);

    expect(screen.getByTestId('missing-type-dialog')).toHaveAttribute('data-open', 'true');
    await user.click(screen.getByLabelText('missing-type-confirm'));

    expect(onMissingTypeCancelSpy).not.toHaveBeenCalledWith();
    expect(formStore.changeType).toHaveBeenCalledWith('default');
});

test('Should call the onMissingTypeCancel callback if MissingTypeDialog is cancelled', async() => {
    const user = userEvent.setup();
    const onMissingTypeCancelSpy = jest.fn();
    const resourceStore = new ResourceStore('snippet', '1');
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    (formStore: any).hasInvalidType = true;

    renderForm({
        onMissingTypeCancel: onMissingTypeCancelSpy,
    }, formStore);

    expect(screen.getByTestId('missing-type-dialog')).toHaveAttribute('data-open', 'true');
    await user.click(screen.getByLabelText('missing-type-cancel'));

    expect(onMissingTypeCancelSpy).toHaveBeenCalledWith();
});
