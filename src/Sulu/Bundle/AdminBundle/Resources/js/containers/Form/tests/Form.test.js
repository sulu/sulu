// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import {observable} from 'mobx';
import log from 'loglevel';
import Form from '../Form';
import Renderer from '../Renderer';
import GhostDialog from '../GhostDialog';
import MissingTypeDialog from '../MissingTypeDialog';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../stores/ResourceFormStore';

jest.mock('../../../services/Router/Router', () => jest.fn());

jest.mock('../Renderer', () => jest.fn(() => null));
jest.mock('../GhostDialog', () => jest.fn(() => null));
jest.mock('../MissingTypeDialog', () => jest.fn(() => null));

jest.mock('../stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.id = resourceStore.id;
    this.resourceKey = resourceStore.resourceKey;
    this.data = resourceStore.data;
    this.locale = resourceStore.observableOptions.locale;
    this.loading = resourceStore.loading;
    this.validate = jest.fn().mockReturnValue(true);
    this.schema = {};
    this.errors = {};
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

function FormWithSubmitButton({submitOptions, ...props}: Object) {
    let form;
    const setForm = (formInstance) => {
        form = formInstance;
    };
    const handleSubmit = () => {
        if (!form) {
            throw new Error('Expected Form ref to be set');
        }

        form.submit(submitOptions);
    };

    return (
        <>
            {/* eslint-disable-next-line react/jsx-no-bind */}
            <button onClick={handleSubmit} type="button">submit form</button>
            {/* eslint-disable-next-line react/jsx-no-bind */}
            <Form {...props} ref={setForm} />
        </>
    );
}

function renderForm(props: Object = {}) {
    return render(
        <Form
            onSubmit={jest.fn()}
            store={new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet')}
            {...props}
        />
    );
}

function renderFormWithSubmitButton(props: Object = {}, submitOptions?: mixed) {
    return render(
        <FormWithSubmitButton
            onSubmit={jest.fn()}
            store={new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet')}
            submitOptions={submitOptions}
            {...props}
        />
    );
}

function getAllMockProps(componentMock: Function) {
    const calls = ((componentMock: any).mock.calls: any);
    return calls.map((call) => call[0]);
}

function getLatestMockProps(componentMock: Function) {
    const props = getAllMockProps(componentMock);
    return props[props.length - 1];
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should render form using renderer', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');

    const {asFragment} = renderForm({onSubmit: submitSpy, store});
    expect(Renderer).toBeCalled();
    expect(asFragment()).toMatchSnapshot();
});

test('Render permission hint if permissions are missing', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    // $FlowFixMe
    store.forbidden = true;

    const {asFragment} = renderForm({onSubmit: submitSpy, store});
    expect(asFragment()).toMatchSnapshot();
});

test('Should call onSubmit callback', () => {
    const errorSpy = jest.fn();
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    renderFormWithSubmitButton({onError: errorSpy, onSubmit: submitSpy, store});

    act(() => {
        screen.getByRole('button', {name: 'submit form'}).click();
    });

    expect(errorSpy).not.toBeCalled();
    expect(submitSpy).toBeCalled();
});

test.each([
    ['draft'],
    ['publish'],
])('Call saveHandlers with the action "%s" as argument when form is submitted', async(action) => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();
    const submitPromise = Promise.resolve();
    const submitSpy = jest.fn().mockReturnValue(submitPromise);

    const resourceStore = new ResourceStore('snippet', '1');
    resourceStore.data = {
        block: [
            {
                text: 'Test',
                type: 'default',
            },
        ],
    };

    const store = new ResourceFormStore(resourceStore, 'snippet');
    renderFormWithSubmitButton({onSubmit: submitSpy, store}, action);

    getLatestMockProps(Renderer).formInspector.addSaveHandler(handler1);
    getLatestMockProps(Renderer).formInspector.addSaveHandler(handler2);

    await act(async() => {
        screen.getByRole('button', {name: 'submit form'}).click();
        await submitPromise;
    });

    expect(handler1).toBeCalledWith(action);
    expect(handler2).toBeCalledWith(action);
    expect(log.warn).toBeCalled();
});

test.each([
    [undefined],
    [{action: 'draft'}],
    [{inherit: true}],
])('Call saveHandlers with the action "%s" as argument when form is submitted', async(action) => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();
    const submitPromise = Promise.resolve();
    const submitSpy = jest.fn().mockReturnValue(submitPromise);

    const resourceStore = new ResourceStore('snippet', '1');
    resourceStore.data = {
        block: [
            {
                text: 'Test',
                type: 'default',
            },
        ],
    };

    const store = new ResourceFormStore(resourceStore, 'snippet');
    renderFormWithSubmitButton({onSubmit: submitSpy, store}, action);

    getLatestMockProps(Renderer).formInspector.addSaveHandler(handler1);
    getLatestMockProps(Renderer).formInspector.addSaveHandler(handler2);

    await act(async() => {
        screen.getByRole('button', {name: 'submit form'}).click();
        await submitPromise;
    });

    expect(handler1).toBeCalledWith(action);
    expect(handler2).toBeCalledWith(action);
    expect(log.warn).not.toBeCalled();
});

test('Should call onError callback', () => {
    const errorSpy = jest.fn();
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    store.validate.mockReturnValue(false);
    store.errors = {error1: {}};

    renderFormWithSubmitButton({onError: errorSpy, onSubmit: submitSpy, store});

    act(() => {
        screen.getByRole('button', {name: 'submit form'}).click();
    });

    expect(errorSpy).toBeCalledWith(store.errors);
    expect(submitSpy).not.toBeCalled();
});

test('Should work when errors occurs but no onError callback is given', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    store.validate.mockReturnValue(false);
    store.errors = {error1: {}};

    renderFormWithSubmitButton({onSubmit: submitSpy, store});

    act(() => {
        screen.getByRole('button', {name: 'submit form'}).click();
    });

    expect(submitSpy).not.toBeCalled();
});

test('Should validate form when a field has finished being edited', () => {
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    renderForm({onSubmit: jest.fn(), store});

    getLatestMockProps(Renderer).onFieldFinish('/text', '/text');

    expect(store.validate).toBeCalledWith();
});

test('Should validate form before calling finish handlers when a field has finished being edited', () => {
    let validateCalled = false;
    const handler1 = jest.fn(() => {
        expect(validateCalled).toEqual(true);
    });

    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    renderForm({onSubmit: jest.fn(), store});
    getLatestMockProps(Renderer).formInspector.addFinishFieldHandler(handler1);

    store.validate.mockImplementation(() => validateCalled = true);
    getLatestMockProps(Renderer).onFieldFinish('/text', '/text');
});

test('Call finish handlers with dataPath and schemaPath when a section field has finished being edited', () => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();

    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    renderForm({onSubmit: jest.fn(), store});

    getLatestMockProps(Renderer).formInspector.addFinishFieldHandler(handler1);
    getLatestMockProps(Renderer).formInspector.addFinishFieldHandler(handler2);

    getLatestMockProps(Renderer).onFieldFinish('/title', '/highlight/items/title');

    expect(handler1).toHaveBeenLastCalledWith('/title', '/highlight/items/title');
    expect(handler2).toHaveBeenLastCalledWith('/title', '/highlight/items/title');
});

test('Call finish handlers with dataPath and schemaPath when a field has finished being edited', () => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();

    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    renderForm({onSubmit: jest.fn(), store});

    getLatestMockProps(Renderer).formInspector.addFinishFieldHandler(handler1);
    getLatestMockProps(Renderer).formInspector.addFinishFieldHandler(handler2);

    getLatestMockProps(Renderer).onFieldFinish('/article', '/article');

    expect(handler1).toHaveBeenLastCalledWith('/article', '/article');
    expect(handler2).toHaveBeenLastCalledWith('/article', '/article');
});

test('Call finish handlers with dataPath and schemaPath when a block field has finished being edited', () => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();

    const resourceStore = new ResourceStore('snippet', '1');
    resourceStore.data = {
        block: [
            {
                text: 'Test',
                type: 'default',
            },
        ],
    };

    const store = new ResourceFormStore(resourceStore, 'snippet');
    renderForm({onSubmit: jest.fn(), store});

    getLatestMockProps(Renderer).formInspector.addFinishFieldHandler(handler1);
    getLatestMockProps(Renderer).formInspector.addFinishFieldHandler(handler2);

    getLatestMockProps(Renderer).onFieldFinish('/block/0/text', '/block/types/default/form/text');
    getLatestMockProps(Renderer).onFieldFinish('/block', '/block');

    expect(handler1).toHaveBeenCalledWith('/block/0/text', '/block/types/default/form/text');
    expect(handler1).toHaveBeenCalledWith('/block', '/block');
    expect(handler2).toHaveBeenCalledWith('/block/0/text', '/block/types/default/form/text');
    expect(handler2).toHaveBeenCalledWith('/block', '/block');
});

test('Should pass data, onSuccess, router and schema to Renderer', () => {
    const router = new Router();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    const successSpy = jest.fn();

    // $FlowFixMe
    store.schema = {};
    store.data.title = 'Title';
    store.data.description = 'Description';

    renderForm({onSubmit: jest.fn(), onSuccess: successSpy, router, store});

    const rendererProps = getLatestMockProps(Renderer);
    expect(rendererProps).toEqual(expect.objectContaining({
        data: store.data,
        onSuccess: successSpy,
        router,
        schema: store.schema,
        value: store.data,
    }));

    const formInspector = rendererProps.formInspector;
    expect(formInspector.resourceKey).toEqual('snippet');
    expect(formInspector.id).toEqual('1');
});

test('Should pass showAllErrors flag to Renderer when form has been submitted', () => {
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    renderFormWithSubmitButton({onSubmit: jest.fn(), store});

    expect(getLatestMockProps(Renderer).showAllErrors).toEqual(false);

    act(() => {
        screen.getByRole('button', {name: 'submit form'}).click();
    });

    expect(getLatestMockProps(Renderer).showAllErrors).toEqual(true);
});

test('Should change data on store when changed', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    renderForm({onSubmit: submitSpy, store});

    getLatestMockProps(Renderer).onChange('field', 'value', {isDefaultValue: true});
    expect(store.change).toBeCalledWith('field', 'value', {isDefaultValue: true});
});

test('Should change data on store without sections', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    renderForm({onSubmit: submitSpy, store});

    getLatestMockProps(Renderer).onChange('item11', 'value!');
    expect(store.change).toBeCalledWith('item11', 'value!', undefined);
});

test('Should show a GhostDialog if the current locale is not translated', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm({onSubmit: jest.fn(), store: formStore});

    expect(getLatestMockProps(GhostDialog).open).toEqual(true);
});

test('Should not show a GhostDialog if the current locale is translated', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm({onSubmit: jest.fn(), store: formStore});

    expect(getLatestMockProps(GhostDialog).open).toEqual(false);
});

test('Should show a GhostDialog after the locale has been switched to a non-translated one', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm({onSubmit: jest.fn(), store: formStore});
    expect(getLatestMockProps(GhostDialog).open).toEqual(false);

    const {locale} = resourceStore.observableOptions;
    if (!locale) {
        throw new Error('The "locale" must be set!');
    }

    act(() => {
        locale.set('de');
    });

    expect(getLatestMockProps(GhostDialog).open).toEqual(true);
});

test('Should not show a GhostDialog if the entity does not exist yet', () => {
    const resourceStore = new ResourceStore('snippet', undefined, {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm({onSubmit: jest.fn(), store: formStore});

    expect(getAllMockProps(GhostDialog)).toHaveLength(0);
});

test('Should not show a GhostDialog if the entity is not translatable', () => {
    const resourceStore = new ResourceStore('snippet', '1');
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm({onSubmit: jest.fn(), store: formStore});

    expect(getAllMockProps(GhostDialog)).toHaveLength(0);
});

test('Should show a GhostDialog and copy the content if the confirm button is clicked', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    resourceStore.loading = false;
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm({onSubmit: jest.fn(), store: formStore});

    act(() => {
        getLatestMockProps(GhostDialog).onConfirm('en', {});
    });

    expect(formStore.copyFromLocale).toBeCalledWith('en', {});
    expect(getLatestMockProps(GhostDialog).open).toEqual(false);
});

test('Should show a GhostDialog and copy the content if the confirm button is clicked (with additional fields)', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    resourceStore.loading = false;
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm({onSubmit: jest.fn(), store: formStore});

    act(() => {
        getLatestMockProps(GhostDialog).onConfirm('en', {title: 'Test 123'});
    });

    expect(formStore.copyFromLocale).toBeCalledWith('en', {
        title: 'Test 123',
    });
    expect(getLatestMockProps(GhostDialog).open).toEqual(false);
});

test('Should show a GhostDialog and do nothing if the cancel button is clicked', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm({onSubmit: jest.fn(), store: formStore});

    expect(getLatestMockProps(GhostDialog).open).toEqual(true);

    act(() => {
        getLatestMockProps(GhostDialog).onCancel();
    });

    expect(getLatestMockProps(GhostDialog).open).toEqual(false);
    expect(formStore.copyFromLocale).not.toBeCalled();
});

test('Should not show a GhostDialog if the resourceStore is currently loading', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    resourceStore.loading = true;
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    renderForm({onSubmit: jest.fn(), store: formStore});

    expect(getAllMockProps(GhostDialog)).toHaveLength(0);
});

test('Should set the type of the formStore to selected value in MissingTypeDialog', () => {
    const onMissingTypeCancelSpy = jest.fn();

    const resourceStore = new ResourceStore('snippet', '1');
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    // $FlowFixMe
    formStore.hasInvalidType = true;
    formStore.types = {
        default: {key: 'default', title: 'Default'},
    };

    renderForm({onMissingTypeCancel: onMissingTypeCancelSpy, onSubmit: jest.fn(), store: formStore});

    expect(getLatestMockProps(MissingTypeDialog).open).toEqual(true);
    getLatestMockProps(MissingTypeDialog).onConfirm('default');

    expect(onMissingTypeCancelSpy).not.toBeCalledWith();
    expect(formStore.changeType).toBeCalledWith('default');
});

test('Should call the onMissingTypeCancel callback if MissingTypeDialog is cancelled', () => {
    const onMissingTypeCancelSpy = jest.fn();

    const resourceStore = new ResourceStore('snippet', '1');
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    // $FlowFixMe
    formStore.hasInvalidType = true;

    renderForm({onMissingTypeCancel: onMissingTypeCancelSpy, onSubmit: jest.fn(), store: formStore});

    expect(getLatestMockProps(MissingTypeDialog).open).toEqual(true);
    getLatestMockProps(MissingTypeDialog).onCancel();

    expect(onMissingTypeCancelSpy).toBeCalledWith();
});
