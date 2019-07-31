// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, render, shallow} from 'enzyme';
import Form from '../Form';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../stores/ResourceFormStore';
import metadataStore from '../stores/MetadataStore';

jest.mock('../../../services/Router', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../registries/FieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'block':
                return require('../../../containers/FieldBlocks').default;
            case 'text_line':
                return require('../../../components/Input').default;
        }
    }),
    getOptions: jest.fn().mockReturnValue({}),
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
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions = {}) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.data = {};
    this.observableOptions = observableOptions;
    this.loading = false;
}));

jest.mock('../stores/MetadataStore', () => ({
    getSchema: jest.fn(),
}));

test('Should render form using renderer', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');

    const form = render(<Form onSubmit={submitSpy} store={store} />);
    expect(form).toMatchSnapshot();
});

test('Should call onSubmit callback', () => {
    const errorSpy = jest.fn();
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onError={errorSpy} onSubmit={submitSpy} store={store} />);

    form.instance().submit();

    expect(errorSpy).not.toBeCalled();
    expect(submitSpy).toBeCalled();
});

test.each([
    [undefined],
    ['draft'],
    ['publish'],
])('Call saveHandlers with the action "%s" as argument when form is submitted', (action) => {
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

    const form = shallow(<Form onSubmit={submitSpy} store={store} />);

    form.instance().formInspector.addSaveHandler(handler1);
    form.instance().formInspector.addSaveHandler(handler2);

    form.instance().submit(action);

    return submitPromise.then(() => {
        expect(handler1).toBeCalledWith(action);
        expect(handler2).toBeCalledWith(action);
    });
});

test('Should call onError callback', () => {
    const errorSpy = jest.fn();
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    store.validate.mockReturnValue(false);
    store.errors = {error1: {}};
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onError={errorSpy} onSubmit={submitSpy} store={store} />);

    form.instance().submit();

    expect(errorSpy).toBeCalledWith(store.errors);
    expect(submitSpy).not.toBeCalled();
});

test('Should work when errors occurs but no onError callback is given', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    store.validate.mockReturnValue(false);
    store.errors = {error1: {}};
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onSubmit={submitSpy} store={store} />);

    form.instance().submit();

    expect(submitSpy).not.toBeCalled();
});

test('Should validate form when a field has finished being edited', () => {
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onSubmit={jest.fn()} store={store} />);

    form.find('Renderer').prop('onFieldFinish')();

    expect(store.validate).toBeCalledWith();
});

test('Should validate form before calling finish handlers when a field has finished being edited', () => {
    const handler1 = jest.fn(() => {
        expect(validateCalled).toEqual(true);
    });
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    metadataStore.getSchema.mockReturnValue({});

    const form = mount(<Form onSubmit={jest.fn()} store={store} />);
    form.instance().formInspector.addFinishFieldHandler(handler1);

    let validateCalled = false;
    store.validate.mockImplementation(() => validateCalled = true);
    form.find('Renderer').prop('onFieldFinish')();
});

test('Call finish handlers with dataPath and schemaPath when a section field has finished being edited', () => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();

    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    // $FlowFixMe
    store.schema = {
        highlight: {
            items: {
                title: {
                    type: 'text_line',
                },
            },
            type: 'section',
        },
    };
    const form = mount(<Form onSubmit={jest.fn()} store={store} />);
    form.instance().formInspector.addFinishFieldHandler(handler1);
    form.instance().formInspector.addFinishFieldHandler(handler2);

    form.find('Field[name="title"] Input').prop('onFinish')();
    expect(handler1).toHaveBeenLastCalledWith('/title', '/highlight/items/title');
    expect(handler2).toHaveBeenLastCalledWith('/title', '/highlight/items/title');
});

test('Call finish handlers with dataPath and schemaPath when a field has finished being edited', () => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();

    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    // $FlowFixMe
    store.schema = {
        article: {
            type: 'text_line',
        },
    };
    const form = mount(<Form onSubmit={jest.fn()} store={store} />);
    form.instance().formInspector.addFinishFieldHandler(handler1);
    form.instance().formInspector.addFinishFieldHandler(handler2);

    form.find('Field[name="article"] Input').prop('onFinish')();
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
    // $FlowFixMe
    store.schema = {
        block: {
            defaultType: 'default',
            type: 'block',
            types: {
                default: {
                    form: {
                        text: {
                            type: 'text_line',
                        },
                    },
                    title: 'Default',
                },
            },
        },
    };

    const form = mount(<Form onSubmit={jest.fn()} store={store} />);
    form.instance().formInspector.addFinishFieldHandler(handler1);
    form.instance().formInspector.addFinishFieldHandler(handler2);
    form.find('SortableBlockList').prop('onExpand')(0);
    form.update();
    form.find('SortableBlock Field').at(0).instance().handleFinish();
    expect(handler1).toHaveBeenLastCalledWith('/block/0/text', '/block/types/default/form/text');
    expect(handler2).toHaveBeenLastCalledWith('/block/0/text', '/block/types/default/form/text');
});

test('Should pass data, onSuccess, router and schema to Renderer', () => {
    const router = new Router();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    const successSpy = jest.fn();

    // $FlowFixMe
    store.schema = {};
    store.data.title = 'Title';
    store.data.description = 'Description';
    const form = shallow(<Form onSubmit={jest.fn()} onSuccess={successSpy} router={router} store={store} />);

    expect(form.find('Renderer').props()).toEqual(expect.objectContaining({
        data: store.data,
        onSuccess: successSpy,
        router,
        schema: store.schema,
    }));

    const formInspector = form.find('Renderer').prop('formInspector');
    expect(formInspector.resourceKey).toEqual('snippet');
    expect(formInspector.id).toEqual('1');
});

test('Should pass showAllErrors flag to Renderer when form has been submitted', () => {
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={store} />);

    expect(form.find('Renderer').prop('showAllErrors')).toEqual(false);
    form.find(Form).instance().submit();
    form.update();
    expect(form.find('Renderer').prop('showAllErrors')).toEqual(true);
});

test('Should change data on store when changed', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    const form = shallow(<Form onSubmit={submitSpy} store={store} />);

    form.find('Renderer').simulate('change', 'field', 'value');
    expect(store.change).toBeCalledWith('field', 'value');
});

test('Reset dirty flag after form was rendered to avoid initial dirty state if defaults were set by fields', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    store.dirty = true;
    mount(<Form onSubmit={submitSpy} store={store} />);

    expect(store.dirty).toEqual(false);
});

test('Should change data on store without sections', () => {
    const submitSpy = jest.fn();
    const store = new ResourceFormStore(new ResourceStore('snippet', '1'), 'snippet');
    // $FlowFixMe
    store.schema = {
        section1: {
            label: 'Section 1',
            type: 'section',
            items: {
                item11: {
                    label: 'Item 1.1',
                    type: 'text_line',
                },
                section11: {
                    label: 'Section 1.1',
                    type: 'section',
                },
            },
        },
        section2: {
            label: 'Section 2',
            type: 'section',
            items: {
                item21: {
                    label: 'Item 2.1',
                    type: 'text_line',
                },
            },
        },
    };

    const form = mount(<Form onSubmit={submitSpy} store={store} />);
    form.find('Input').at(0).instance().handleChange({currentTarget: {value: 'value!'}});

    expect(store.change).toBeCalledWith('item11', 'value!');
});

test('Should show a GhostDialog if the current locale is not translated', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('GhostDialog').prop('open')).toEqual(true);
});

test('Should not show a GhostDialog if the current locale is translated', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('GhostDialog').prop('open')).toEqual(false);
});

test('Should show a GhostDialog after the locale has been switched to a non-translated one', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('GhostDialog').prop('open')).toEqual(false);

    const {locale} = resourceStore.observableOptions;
    if (!locale) {
        throw new Error('The "locale" must be set!');
    }
    locale.set('de');

    form.update();
    expect(form.find('GhostDialog').prop('open')).toEqual(true);
});

test('Should not show a GhostDialog if the entity does not exist yet', () => {
    const resourceStore = new ResourceStore('snippet', undefined, {locale: observable.box('en')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('GhostDialog')).toHaveLength(0);
});

test('Should not show a GhostDialog if the entity is not translatable', () => {
    const resourceStore = new ResourceStore('snippet', '1');
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('GhostDialog')).toHaveLength(0);
});

test('Should show a GhostDialog and copy the content if the confirm button is clicked', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('GhostDialog').prop('open')).toEqual(true);
    form.find('GhostDialog Button[skin="primary"]').simulate('click');
    expect(form.find('GhostDialog').prop('open')).toEqual(false);

    expect(formStore.copyFromLocale).toBeCalledWith('en');
});

test('Should show a GhostDialog and do nothing if the cancel button is clicked', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.find('GhostDialog').prop('open')).toEqual(true);
    form.find('GhostDialog Button[skin="secondary"]').simulate('click');
    expect(form.find('GhostDialog').prop('open')).toEqual(false);

    expect(formStore.copyFromLocale).not.toBeCalled();
});

test('Should not show a GhostDialog if the resourceStore is currently loading', () => {
    const resourceStore = new ResourceStore('snippet', '1', {locale: observable.box('de')});
    resourceStore.data.availableLocales = ['en'];
    resourceStore.loading = true;
    const formStore = new ResourceFormStore(resourceStore, 'snippet');
    const form = mount(<Form onSubmit={jest.fn()} store={formStore} />);

    expect(form.instance().displayGhostDialog).toEqual(false);
});
