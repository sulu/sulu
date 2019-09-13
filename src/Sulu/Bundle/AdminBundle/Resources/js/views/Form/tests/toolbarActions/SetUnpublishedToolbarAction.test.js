// @flow
import {mount} from 'enzyme';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceRequester from '../../../../services/ResourceRequester';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import SetUnpublishedToolbarAction from '../../toolbarActions/SetUnpublishedToolbarAction';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.id = id;
    this.data = {};
    this.observableOptions = observableOptions;
    this.locale = {
        get: jest.fn(),
    };
}));

jest.mock('../../../../services/ResourceRequester', () => ({
    post: jest.fn(),
}));

jest.mock('../../../../containers/Form/stores/ResourceFormStore', () => (
    class {
        resourceStore;
        options = {};

        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        get id() {
            return this.resourceStore.id;
        }

        get locale() {
            return this.resourceStore.locale;
        }

        get data() {
            return this.resourceStore.data;
        }

        delete = jest.fn();
        setMultiple = jest.fn();
    })
);

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
    this.route = {
        options: {},
    };
}));

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
}));

function createSetUnpublishedToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test');
    const formStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new SetUnpublishedToolbarAction(formStore, form, router, [], options);
}

test('Return enabled item config', () => {
    const setUnpublishedToolbarAction = createSetUnpublishedToolbarAction();
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.id = 5;
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.data.published = true;
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.data.publishedState = false;

    expect(setUnpublishedToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
        label: 'sulu_page.unpublish',
    }));
});

test('Return item config if condition is met', () => {
    const setUnpublishedToolbarAction = createSetUnpublishedToolbarAction({display_condition: '_permission.edit'});
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.data._permission = {edit: true};

    const toolbarItemConfig = setUnpublishedToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    expect(toolbarItemConfig).toEqual(expect.objectContaining({
        label: 'sulu_page.unpublish',
    }));
});

test('Return empty item config if conditions are not met', () => {
    const setUnpublishedToolbarAction = createSetUnpublishedToolbarAction({display_condition: '_permission.live'});

    const toolbarItemConfig = setUnpublishedToolbarAction.getToolbarItemConfig();

    expect(toolbarItemConfig).toEqual(undefined);
});

test('Return disabled delete draft and unpublish items when page is not published', () => {
    const setUnpublishedToolbarAction = createSetUnpublishedToolbarAction();
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.id = 5;
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.data.published = false;
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.data.publishedState = false;

    const toolbarItemConfig = setUnpublishedToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    expect(toolbarItemConfig).toEqual(expect.objectContaining({
        disabled: true,
        label: 'sulu_page.unpublish',
    }));
});

test('Return disabled delete draft item when page has no draft', () => {
    const setUnpublishedToolbarAction = createSetUnpublishedToolbarAction();
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.id = 5;
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.data.published = true;
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.data.publishedState = true;

    const toolbarItemConfig = setUnpublishedToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    expect(toolbarItemConfig).toEqual(expect.objectContaining({
        disabled: false,
        label: 'sulu_page.unpublish',
    }));
});

test('Return disabled item config', () => {
    const setUnpublishedToolbarAction = createSetUnpublishedToolbarAction();
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.id = undefined;

    expect(setUnpublishedToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
        label: 'sulu_page.unpublish',
    }));
});

test('Return no dialog if no id is set', () => {
    const setUnpublishedToolbarAction = createSetUnpublishedToolbarAction();
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.id = undefined;

    expect(setUnpublishedToolbarAction.getNode()).toEqual(null);
});

test('Throw error if no locale is given', () => {
    const setUnpublishedToolbarAction = createSetUnpublishedToolbarAction();
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.locale = undefined;

    expect(() => setUnpublishedToolbarAction.getNode()).toThrow('locale');
});

test('Close dialog when onClose from unpublish dialog is called', () => {
    const setUnpublishedToolbarAction = createSetUnpublishedToolbarAction();
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    setUnpublishedToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = setUnpublishedToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const clickHandler = toolbarItemConfig.onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the unpublish option');
    }

    let element = mount(setUnpublishedToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    clickHandler();
    element = mount(setUnpublishedToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.prop('onCancel')();
    element = mount(setUnpublishedToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Unpublish page when dialog is confirmed', () => {
    const data = {
        title: 'Title',
    };

    const unpublishPromise = Promise.resolve(data);
    ResourceRequester.post.mockReturnValue(unpublishPromise);

    const setUnpublishedToolbarAction = createSetUnpublishedToolbarAction();
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    setUnpublishedToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    setUnpublishedToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = setUnpublishedToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const clickHandler = toolbarItemConfig.onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the unpublish option');
    }

    let element = mount(setUnpublishedToolbarAction.getNode());
    clickHandler();

    expect(element.prop('confirmLoading')).toEqual(false);
    element.prop('onConfirm')();
    element = mount(setUnpublishedToolbarAction.getNode());
    expect(element.prop('confirmLoading')).toEqual(true);
    expect(ResourceRequester.post).toBeCalledWith(
        'pages',
        undefined,
        {action: 'unpublish', id: 3, locale: setUnpublishedToolbarAction.resourceFormStore.locale, webspace: 'sulu_io'}
    );

    return unpublishPromise.then(() => {
        element = mount(setUnpublishedToolbarAction.getNode());
        expect(element.prop('confirmLoading')).toEqual(false);
        expect(setUnpublishedToolbarAction.resourceFormStore.setMultiple).toBeCalledWith(data);
        expect(setUnpublishedToolbarAction.resourceFormStore.dirty).toEqual(false);
    });
});
