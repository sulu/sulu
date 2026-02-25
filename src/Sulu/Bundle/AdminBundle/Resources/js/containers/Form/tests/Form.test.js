// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {observable} from 'mobx';
import log from 'loglevel';
import Form from '../Form';
import Renderer from '../Renderer';
import GhostDialog from '../GhostDialog';
import MissingTypeDialog from '../MissingTypeDialog';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../stores/ResourceFormStore';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
    debug: jest.fn(),
}));

jest.mock('../../../services/Router/Router', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../Renderer', () => {
    const RendererMock: any = jest.fn(function RendererMock() {
        return <div data-testid="renderer" />;
    });

    return RendererMock;
});

jest.mock('../GhostDialog', () => {
    const GhostDialogMock: any = jest.fn(function GhostDialogMock() {
        return <div data-testid="ghost-dialog" />;
    });

    return GhostDialogMock;
});

jest.mock('../MissingTypeDialog', () => {
    const MissingTypeDialogMock: any = jest.fn(function MissingTypeDialogMock() {
        return <div data-testid="missing-type-dialog" />;
    });

    return MissingTypeDialogMock;
});

jest.mock('../stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.id = resourceStore.id;
    this.resourceKey = resourceStore.resourceKey;
    this.data = resourceStore.data;
    this.locale = resourceStore.observableOptions.locale;
    this.loading = resourceStore.loading;
    this.options = {};
    this.metadataOptions = {};
    this.errors = {};
    this.forbidden = false;
    this.notFound = false;
    this.unexpectedError = false;
    this.hasInvalidType = false;
    this.validate = jest.fn().mockReturnValue(true);
    this.schema = {};
    this.set = jest.fn();
    this.change = jest.fn();
    this.finishField = jest.fn();
    this.isFieldModified = jest.fn();
    this.copyFromLocale = jest.fn();
    this.getValueByPath = jest.fn();
    this.getSchemaEntryByPath = jest.fn().mockReturnValue({types: {default: {form: {}}}});
    this.getValuesByTag = jest.fn().mockReturnValue([]);
    this.getPathsByTag = jest.fn().mockReturnValue([]);
    this.types = {};
    this.changeType = jest.fn();
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions = {}) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.data = {};
    this.observableOptions = observableOptions;
    this.loading = false;
    this.forbidden = false;
    this.notFound = false;
    this.unexpectedError = false;
}));

const logMock: any = log;
const RendererMock: any = Renderer;
const GhostDialogMock: any = GhostDialog;
const MissingTypeDialogMock: any = MissingTypeDialog;

function getRendererProps() {
    return getLatestMockProps(RendererMock);
}

function getGhostDialogProps() {
    return getLatestMockProps(GhostDialogMock);
}

function getMissingTypeDialogProps() {
    return getLatestMockProps(MissingTypeDialogMock);
}

function renderForm(store: any, props: Object = {}) {
    const formRef: any = React.createRef();
    const view = render(
        <Form
            onSubmit={jest.fn()}
            ref={formRef}
            store={store}
            {...props}
        />
    );

    if (!formRef.current) {
        throw new Error('Form ref was not set');
    }

    return {formRef, ...view};
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should render form using renderer', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');

    const {asFragment} = renderForm(store, {onSubmit: submitSpy});
    expect(asFragment()).toMatchSnapshot();
});

test('Render permission hint if permissions are missing', () => {
    const submitSpy = jest.fn();
    const store: any = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    store.forbidden = true;

    const {asFragment} = renderForm(store, {onSubmit: submitSpy});
    expect(asFragment()).toMatchSnapshot();
});

test('Should call onSubmit callback', () => {
    const errorSpy = jest.fn();
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');

    const {formRef} = renderForm(store, {onError: errorSpy, onSubmit: submitSpy});

    act(() => {
        formRef.current.submit();
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

    const {formRef} = renderForm(store, {onSubmit: submitSpy});

    formRef.current.formInspector.addSaveHandler(handler1);
    formRef.current.formInspector.addSaveHandler(handler2);

    await formRef.current.submit(action);

    expect(handler1).toBeCalledWith(action);
    expect(handler2).toBeCalledWith(action);
    expect(logMock.warn).toBeCalled();
});

test.each([
    [undefined],
    [{action: 'draft'}],
    [{inherit: true}],
])('Call saveHandlers with options "%s" as argument when form is submitted', async(options) => {
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

    const {formRef} = renderForm(store, {onSubmit: submitSpy});

    formRef.current.formInspector.addSaveHandler(handler1);
    formRef.current.formInspector.addSaveHandler(handler2);

    await formRef.current.submit(options);

    expect(handler1).toBeCalledWith(options);
    expect(handler2).toBeCalledWith(options);
    expect(logMock.warn).not.toBeCalled();
});

test('Should call onError callback', () => {
    const errorSpy = jest.fn();
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    store.validate.mockReturnValue(false);
    store.errors = {error1: {}};

    const {formRef} = renderForm(store, {onError: errorSpy, onSubmit: submitSpy});

    act(() => {
        formRef.current.submit();
    });

    expect(errorSpy).toBeCalledWith(store.errors);
    expect(submitSpy).not.toBeCalled();
});

test('Should work when errors occurs but no onError callback is given', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    store.validate.mockReturnValue(false);
    store.errors = {error1: {}};

    const {formRef} = renderForm(store, {onSubmit: submitSpy});

    act(() => {
        formRef.current.submit();
    });

    expect(submitSpy).not.toBeCalled();
});

test('Should validate form when a field has finished being edited', () => {
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');

    renderForm(store, {onSubmit: jest.fn()});

    act(() => {
        getRendererProps().onFieldFinish('/article', '/article');
    });

    expect(store.validate).toBeCalledWith();
    expect(store.finishField).toBeCalledWith('/article');
});

test('Should validate form before calling finish handlers when a field has finished being edited', () => {
    let validateCalled = false;
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    const {formRef} = renderForm(store, {onSubmit: jest.fn()});

    const handler1 = jest.fn(() => {
        expect(validateCalled).toEqual(true);
    });

    formRef.current.formInspector.addFinishFieldHandler(handler1);

    store.validate.mockImplementation(() => validateCalled = true);

    act(() => {
        getRendererProps().onFieldFinish('/title', '/highlight/items/title');
    });

    expect(handler1).toBeCalledWith('/title', '/highlight/items/title');
});

test.each([
    ['/title', '/highlight/items/title'],
    ['/article', '/article'],
    ['/block/0/text', '/block/types/default/form/text'],
])('Call finish handlers with dataPath "%s" and schemaPath "%s"', (dataPath, schemaPath) => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();

    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    const {formRef} = renderForm(store, {onSubmit: jest.fn()});

    formRef.current.formInspector.addFinishFieldHandler(handler1);
    formRef.current.formInspector.addFinishFieldHandler(handler2);

    act(() => {
        getRendererProps().onFieldFinish(dataPath, schemaPath);
    });

    expect(handler1).toHaveBeenLastCalledWith(dataPath, schemaPath);
    expect(handler2).toHaveBeenLastCalledWith(dataPath, schemaPath);
});

test('Should pass data, onSuccess, router and schema to Renderer', () => {
    const router = new Router();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    const successSpy = jest.fn();

    store.schema = {};
    store.data.title = 'Title';
    store.data.description = 'Description';

    renderForm(store, {onSubmit: jest.fn(), onSuccess: successSpy, router});

    expect(getRendererProps()).toEqual(expect.objectContaining({
        data: store.data,
        onSuccess: successSpy,
        router,
        schema: store.schema,
        value: store.data,
    }));

    const formInspector = getRendererProps().formInspector;
    expect(formInspector.resourceKey).toEqual('snippet');
    expect(formInspector.id).toEqual('1');
});

test('Should pass showAllErrors flag to Renderer when form has been submitted', () => {
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    const {formRef} = renderForm(store, {onSubmit: jest.fn()});

    expect(getRendererProps().showAllErrors).toEqual(false);

    act(() => {
        formRef.current.submit();
    });

    expect(getRendererProps().showAllErrors).toEqual(true);
});

test('Should change data on store when changed', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');

    renderForm(store, {onSubmit: submitSpy});

    act(() => {
        getRendererProps().onChange('field', 'value', {isDefaultValue: true});
    });

    expect(store.change).toBeCalledWith('field', 'value', {isDefaultValue: true});
});

test('Should show a GhostDialog if the current locale is not translated', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm(formStore, {onSubmit: jest.fn()});

    expect(getGhostDialogProps().open).toEqual(true);
});

test('Should not show a GhostDialog if the current locale is translated', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm(formStore, {onSubmit: jest.fn()});

    expect(getGhostDialogProps().open).toEqual(false);
});

test('Should show a GhostDialog after the locale has been switched to a non-translated one', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm(formStore, {onSubmit: jest.fn()});

    expect(getGhostDialogProps().open).toEqual(false);

    const {locale} = resourceStore.observableOptions;
    if (!locale) {
        throw new Error('The "locale" must be set!');
    }

    act(() => {
        locale.set('de');
    });

    expect(getGhostDialogProps().open).toEqual(true);
});

test('Should not show a GhostDialog if the entity does not exist yet', () => {
    const resourceStore = new ResourceStore('snippet', undefined, {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm(formStore, {onSubmit: jest.fn()});

    expect(GhostDialogMock).not.toBeCalled();
});

test('Should not show a GhostDialog if the entity is not translatable', () => {
    const resourceStore = new ResourceStore('snippet', '1');
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm(formStore, {onSubmit: jest.fn()});

    expect(GhostDialogMock).not.toBeCalled();
});

test('Should show a GhostDialog and copy the content if the confirm button is clicked', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    resourceStore.loading = false;
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm(formStore, {onSubmit: jest.fn()});

    act(() => {
        getGhostDialogProps().onConfirm('en', {});
    });

    expect(getGhostDialogProps().open).toEqual(false);
    expect(formStore.copyFromLocale).toBeCalledWith('en', {});
});

test('Should show a GhostDialog and copy additional fields on confirm', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm(formStore, {onSubmit: jest.fn()});

    act(() => {
        getGhostDialogProps().onConfirm('en', {title: 'Test 123'});
    });

    expect(getGhostDialogProps().open).toEqual(false);
    expect(formStore.copyFromLocale).toBeCalledWith('en', {
        title: 'Test 123',
    });
});

test('Should show a GhostDialog and do nothing if the cancel button is clicked', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm(formStore, {onSubmit: jest.fn()});

    expect(getGhostDialogProps().open).toEqual(true);

    act(() => {
        getGhostDialogProps().onCancel();
    });

    expect(getGhostDialogProps().open).toEqual(false);
    expect(formStore.copyFromLocale).not.toBeCalled();
});

test('Should not show a GhostDialog if the resourceStore is currently loading', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    resourceStore.loading = true;
    const formStore = new ResourceFormStore(resourceStore, 'snippet');

    renderForm(formStore, {onSubmit: jest.fn()});

    expect(GhostDialogMock).not.toBeCalled();
});

test('Should set the type of the formStore to selected value in MissingTypeDialog', () => {
    const onMissingTypeCancelSpy = jest.fn();

    const resourceStore = new ResourceStore('snippet', '1');
    const formStore: any = new ResourceFormStore(resourceStore, 'snippet');
    formStore.hasInvalidType = true;
    formStore.types = {
        default: {key: 'default', title: 'Default'},
    };

    renderForm(formStore, {onMissingTypeCancel: onMissingTypeCancelSpy, onSubmit: jest.fn()});

    expect(getMissingTypeDialogProps().open).toEqual(true);

    act(() => {
        getMissingTypeDialogProps().onConfirm('default');
    });

    expect(onMissingTypeCancelSpy).not.toBeCalledWith();
    expect(formStore.changeType).toBeCalledWith('default');
});

test('Should call the onMissingTypeCancel callback if MissingTypeDialog is cancelled', () => {
    const onMissingTypeCancelSpy = jest.fn();

    const resourceStore = new ResourceStore('snippet', '1');
    const formStore: any = new ResourceFormStore(resourceStore, 'snippet');
    formStore.hasInvalidType = true;

    renderForm(formStore, {onMissingTypeCancel: onMissingTypeCancelSpy, onSubmit: jest.fn()});

    expect(getMissingTypeDialogProps().open).toEqual(true);

    act(() => {
        getMissingTypeDialogProps().onCancel();
    });

    expect(onMissingTypeCancelSpy).toBeCalledWith();
});
