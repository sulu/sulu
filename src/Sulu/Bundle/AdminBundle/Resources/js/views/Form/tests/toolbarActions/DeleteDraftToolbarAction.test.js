// @flow
import {mount} from 'enzyme';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceRequester from '../../../../services/ResourceRequester';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import DeleteDraftToolbarAction from '../../toolbarActions/DeleteDraftToolbarAction';

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

function createDeleteDraftToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test');
    const formStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new DeleteDraftToolbarAction(formStore, form, router, [], options);
}

test('Return enabled item config', () => {
    const deleteDraftToolbarAction = createDeleteDraftToolbarAction();
    deleteDraftToolbarAction.resourceFormStore.resourceStore.id = 5;
    deleteDraftToolbarAction.resourceFormStore.resourceStore.data.published = true;
    deleteDraftToolbarAction.resourceFormStore.resourceStore.data.publishedState = false;

    expect(deleteDraftToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
        label: 'sulu_page.delete_draft',
        type: 'button',
    }));
});

test('Return no item config if condition is not met', () => {
    const deleteDraftToolbarAction = createDeleteDraftToolbarAction({display_condition: '_permission.live'});

    const toolbarItemConfig = deleteDraftToolbarAction.getToolbarItemConfig();
    expect(toolbarItemConfig).toEqual(undefined);
});

test('Return item config if condition is met', () => {
    const deleteDraftToolbarAction = createDeleteDraftToolbarAction({display_condition: '_permission.edit'});
    deleteDraftToolbarAction.resourceFormStore.resourceStore.data._permission = {edit: true};

    const toolbarItemConfig = deleteDraftToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    expect(toolbarItemConfig).toEqual(expect.objectContaining({disabled: true, label: 'sulu_page.delete_draft'}));
});

test('Return disabled item config when page is not published', () => {
    const deleteDraftToolbarAction = createDeleteDraftToolbarAction();
    deleteDraftToolbarAction.resourceFormStore.resourceStore.id = 5;
    deleteDraftToolbarAction.resourceFormStore.resourceStore.data.published = false;
    deleteDraftToolbarAction.resourceFormStore.resourceStore.data.publishedState = false;

    const toolbarItemConfig = deleteDraftToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    expect(toolbarItemConfig).toEqual(expect.objectContaining({
        disabled: true,
        label: 'sulu_page.delete_draft',
    }));
});

test('Return disabled item config when page has no draft', () => {
    const deleteDraftToolbarAction = createDeleteDraftToolbarAction();
    deleteDraftToolbarAction.resourceFormStore.resourceStore.id = 5;
    deleteDraftToolbarAction.resourceFormStore.resourceStore.data.published = true;
    deleteDraftToolbarAction.resourceFormStore.resourceStore.data.publishedState = true;

    const toolbarItemConfig = deleteDraftToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    expect(toolbarItemConfig).toEqual(expect.objectContaining({
        disabled: true,
        label: 'sulu_page.delete_draft',
    }));
});

test('Return no dialog if no id is set', () => {
    const deleteDraftToolbarAction = createDeleteDraftToolbarAction();
    deleteDraftToolbarAction.resourceFormStore.resourceStore.id = undefined;

    expect(deleteDraftToolbarAction.getNode()).toEqual(null);
});

test('Close dialog when onClose from delete draft dialog is called', () => {
    const deleteDraftToolbarAction = createDeleteDraftToolbarAction();
    deleteDraftToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    deleteDraftToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    deleteDraftToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = deleteDraftToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const clickHandler = toolbarItemConfig.onClick;
    if (!clickHandler) {
        throw new Error('An onClick callback should be registered on the delete draft option');
    }

    let element = mount(deleteDraftToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    clickHandler();
    element = mount(deleteDraftToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.prop('onCancel')();
    element = mount(deleteDraftToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Delete draft when dialog is confirmed', () => {
    const data = {
        title: 'Title',
    };

    const deleteDraftPromise = Promise.resolve(data);
    ResourceRequester.post.mockReturnValue(deleteDraftPromise);

    const deleteDraftToolbarAction = createDeleteDraftToolbarAction();
    deleteDraftToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    deleteDraftToolbarAction.resourceFormStore.resourceKey = 'snippets';
    // $FlowFixMe
    deleteDraftToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    deleteDraftToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = deleteDraftToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const clickHandler = toolbarItemConfig.onClick;
    if (!clickHandler) {
        throw new Error('An onClick callback should be registered on the delete draft option');
    }

    let element = mount(deleteDraftToolbarAction.getNode());
    clickHandler();

    expect(element.prop('confirmLoading')).toEqual(false);
    element.prop('onConfirm')();
    element = mount(deleteDraftToolbarAction.getNode());
    expect(element.prop('confirmLoading')).toEqual(true);
    expect(ResourceRequester.post).toBeCalledWith(
        'snippets',
        undefined,
        {action: 'remove-draft', id: 3, locale: deleteDraftToolbarAction.resourceFormStore.locale, webspace: 'sulu_io'}
    );

    return deleteDraftPromise.then(() => {
        element = mount(deleteDraftToolbarAction.getNode());
        expect(element.prop('confirmLoading')).toEqual(false);
        expect(deleteDraftToolbarAction.resourceFormStore.setMultiple).toBeCalledWith(data);
        expect(deleteDraftToolbarAction.resourceFormStore.dirty).toEqual(false);
    });
});
