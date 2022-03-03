// @flow
import {mount} from 'enzyme';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceRequester from '../../../../services/ResourceRequester';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import CopyToolbarAction from '../../toolbarActions/CopyToolbarAction';
import conditionDataProviderRegistry from '../../../../containers/Form/registries/conditionDataProviderRegistry';

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
        changeMultiple = jest.fn();
    })
);

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
    this.route = {
        name: 'current_route_name',
        options: {},
    };
}));

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.showSuccessSnackbar = jest.fn();
}));

function createCopyToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test');
    const formStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new CopyToolbarAction(formStore, form, router, [], options, resourceStore);
}

test('Return item config with correct disabled, type and label', () => {
    const copyToolbarAction = createCopyToolbarAction();

    expect(copyToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
        label: 'sulu_admin.create_copy',
        type: 'button',
    }));
});

test('Return  item config with enabled button if form store contains an id', () => {
    const copyToolbarAction = createCopyToolbarAction();
    copyToolbarAction.resourceFormStore.resourceStore.id = 123;

    expect(copyToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
    }));
});

test('Return item config if passed visible_condition is met', () => {
    const copyToolbarAction = createCopyToolbarAction({visible_condition: '_permission.edit'});
    copyToolbarAction.resourceFormStore.resourceStore.data._permission = {edit: true};

    expect(copyToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        label: 'sulu_admin.create_copy',
    }));
});

test('Return empty item config if passed visible_condition is not met', () => {
    const copyToolbarAction = createCopyToolbarAction({visible_condition: '_permission.edit'});
    copyToolbarAction.resourceFormStore.resourceStore.data._permission = {edit: false};

    expect(copyToolbarAction.getToolbarItemConfig()).toEqual(undefined);
});

test('Include data of conditionDataProviderRegistry when evaluating passed visible_condition', () => {
    const copyToolbarAction = createCopyToolbarAction({visible_condition: '__conditionDataProviderValue'});
    expect(copyToolbarAction.getToolbarItemConfig()).toBeUndefined();

    conditionDataProviderRegistry.add(() => ({__conditionDataProviderValue: true}));
    expect(copyToolbarAction.getToolbarItemConfig()).toBeDefined();

    conditionDataProviderRegistry.clear();
    conditionDataProviderRegistry.add(() => ({__conditionDataProviderValue: false}));
    expect(copyToolbarAction.getToolbarItemConfig()).toBeUndefined();
});

test('Display confirmation dialog when button is clicked', () => {
    const copyToolbarAction = createCopyToolbarAction();
    copyToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    copyToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    copyToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = copyToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const onClickCallback = toolbarItemConfig.onClick;
    if (!onClickCallback) {
        throw new Error('A onClick callback should be registered on the unpublish option');
    }

    let element = mount(copyToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    onClickCallback();
    element = mount(copyToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    expect(element.render()).toMatchSnapshot();
});

test('Close confirmation dialog when onCancel callback is fired', () => {
    const copyToolbarAction = createCopyToolbarAction();
    copyToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    copyToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    copyToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = copyToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const onClickCallback = toolbarItemConfig.onClick;
    if (!onClickCallback) {
        throw new Error('A onClick callback should be registered on the unpublish option');
    }

    let element = mount(copyToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    onClickCallback();
    element = mount(copyToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.prop('onCancel')();
    element = mount(copyToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Copy resource when confirmation dialog is confirmed', () => {
    const copyPromise = Promise.resolve({id: 'copied-id', webspace: 'copied-webspace'});
    ResourceRequester.post.mockReturnValue(copyPromise);

    const copyToolbarAction = createCopyToolbarAction();
    copyToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    copyToolbarAction.resourceFormStore.resourceKey = 'pages';
    // $FlowFixMe
    copyToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    copyToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = copyToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const clickHandler = toolbarItemConfig.onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the unpublish option');
    }

    let element = mount(copyToolbarAction.getNode());
    clickHandler();

    expect(element.prop('confirmLoading')).toEqual(false);
    element.prop('onConfirm')();
    element = mount(copyToolbarAction.getNode());
    expect(element.prop('confirmLoading')).toEqual(true);
    expect(ResourceRequester.post).toBeCalledWith(
        'pages',
        undefined,
        {action: 'copy', id: 3, webspace: 'sulu_io'}
    );

    return copyPromise.then(() => {
        element = mount(copyToolbarAction.getNode());
        expect(copyToolbarAction.form.showSuccessSnackbar).toBeCalledWith();
        expect(element.prop('confirmLoading')).toEqual(false);
        expect(copyToolbarAction.router.navigate).toBeCalledWith(
            'current_route_name',
            {id: 'copied-id', webspace: 'copied-webspace'}
        );
    });
});
