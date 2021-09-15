// @flow
import {mount} from 'enzyme';
import TypeToolbarAction from '../../toolbarActions/TypeToolbarAction';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn());

jest.mock('../../../../containers/Form/stores/ResourceFormStore', () => (
    class {
        data = {};
        resourceStore;
        types = {};
        typesLoading = true;

        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        get id() {
            return this.resourceStore.id;
        }

        get dirty() {
            return this.resourceStore.dirty;
        }

        get type() {
            return this.data.template;
        }

        changeType = jest.fn();
    }
));

jest.mock('../../../../services/Router', () => jest.fn());

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
}));

function createTypeToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new TypeToolbarAction(resourceFormStore, form, router, [], options, resourceStore);
}

test('Return item config with correct disabled, loading, options, icon, type and value ', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.resourceFormStore.typesLoading = false;
    typeToolbarAction.resourceFormStore.data.template = 'default';
    typeToolbarAction.resourceFormStore.types = {
        homepage: {
            key: 'homepage',
            title: 'Homepage',
        },
        default: {
            key: 'default',
            title: 'Default',
        },
    };

    expect(typeToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-brush',
        loading: false,
        options: [
            {
                label: 'Homepage',
                value: 'homepage',
            },
            {
                label: 'Default',
                value: 'default',
            },
        ],
        type: 'select',
        value: 'default',
    }));
});

test('Return item config with options sorted by title if sort_by is set to title', () => {
    const typeToolbarAction = createTypeToolbarAction({sort_by: 'title'});
    typeToolbarAction.resourceFormStore.typesLoading = false;
    typeToolbarAction.resourceFormStore.data.template = 'default';
    typeToolbarAction.resourceFormStore.types = {
        homepage: {
            key: 'homepage',
            title: 'Homepage',
        },
        default: {
            key: 'default',
            title: 'Default',
        },
    };

    expect(typeToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        options: [
            {
                label: 'Default',
                value: 'default',
            },
            {
                label: 'Homepage',
                value: 'homepage',
            },
        ],
    }));
});

test('Return item config with loading select', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.resourceFormStore.typesLoading = true;
    typeToolbarAction.resourceFormStore.data.template = 'homepage';
    typeToolbarAction.resourceFormStore.types = {};

    expect(typeToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        loading: true,
        options: [],
        value: 'homepage',
    }));
});

test('Change the type of the FormStore when FormStore is not dirty and another type is selected', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.resourceFormStore.resourceStore.dirty = false;
    typeToolbarAction.resourceFormStore.typesLoading = false;
    typeToolbarAction.resourceFormStore.data.template = 'default';
    typeToolbarAction.resourceFormStore.types = {
        default: {
            key: 'default',
            title: 'Default',
        },
        homepage: {
            key: 'homepage',
            title: 'Homepage',
        },
    };

    const toolbarItemConfig = typeToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    if (toolbarItemConfig.type !== 'select') {
        throw new Error(
            'The returned toolbar item must be of type "select", but "' + toolbarItemConfig.type + '" was given!'
        );
    }

    toolbarItemConfig.onChange('homepage');

    expect(typeToolbarAction.resourceFormStore.changeType).toBeCalledWith('homepage');
});

test('Display warning dialog when FormStore is dirty and another type is selected', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.resourceFormStore.resourceStore.dirty = true;
    typeToolbarAction.resourceFormStore.typesLoading = false;
    typeToolbarAction.resourceFormStore.data.template = 'default';
    typeToolbarAction.resourceFormStore.types = {
        default: {
            key: 'default',
            title: 'Default',
        },
        homepage: {
            key: 'homepage',
            title: 'Homepage',
        },
    };

    const toolbarItemConfig = typeToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    if (toolbarItemConfig.type !== 'select') {
        throw new Error(
            'The returned toolbar item must be of type "select", but "' + toolbarItemConfig.type + '" was given!'
        );
    }

    expect(mount(typeToolbarAction.getNode()).instance().props).toEqual(expect.objectContaining({
        title: 'sulu_admin.change_type_dirty_warning_dialog_title',
        children: 'sulu_admin.dirty_warning_dialog_text',
        open: false,
    }));

    toolbarItemConfig.onChange('homepage');

    expect(typeToolbarAction.resourceFormStore.changeType).not.toBeCalled();
    expect(mount(typeToolbarAction.getNode()).instance().props).toEqual(expect.objectContaining({
        title: 'sulu_admin.change_type_dirty_warning_dialog_title',
        children: 'sulu_admin.dirty_warning_dialog_text',
        open: true,
    }));
});

test('Change the type of the FormStore when warning dialog is confirmed', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.resourceFormStore.resourceStore.dirty = true;
    typeToolbarAction.resourceFormStore.typesLoading = false;
    typeToolbarAction.resourceFormStore.data.template = 'default';
    typeToolbarAction.resourceFormStore.types = {
        default: {
            key: 'default',
            title: 'Default',
        },
        homepage: {
            key: 'homepage',
            title: 'Homepage',
        },
    };

    const toolbarItemConfig = typeToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    if (toolbarItemConfig.type !== 'select') {
        throw new Error(
            'The returned toolbar item must be of type "select", but "' + toolbarItemConfig.type + '" was given!'
        );
    }

    toolbarItemConfig.onChange('homepage');
    expect(typeToolbarAction.resourceFormStore.changeType).not.toBeCalled();
    expect(mount(typeToolbarAction.getNode()).instance().props.open).toBeTruthy();

    mount(typeToolbarAction.getNode()).instance().props.onConfirm();
    expect(typeToolbarAction.resourceFormStore.changeType).toBeCalledWith('homepage');
    expect(mount(typeToolbarAction.getNode()).instance().props.open).toBeFalsy();
});

test('Do not change the type of the FormStore when warning dialog is canceled', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.resourceFormStore.resourceStore.dirty = true;
    typeToolbarAction.resourceFormStore.typesLoading = false;
    typeToolbarAction.resourceFormStore.data.template = 'default';
    typeToolbarAction.resourceFormStore.types = {
        default: {
            key: 'default',
            title: 'Default',
        },
        homepage: {
            key: 'homepage',
            title: 'Homepage',
        },
    };

    const toolbarItemConfig = typeToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    if (toolbarItemConfig.type !== 'select') {
        throw new Error(
            'The returned toolbar item must be of type "select", but "' + toolbarItemConfig.type + '" was given!'
        );
    }

    toolbarItemConfig.onChange('homepage');
    expect(typeToolbarAction.resourceFormStore.changeType).not.toBeCalled();
    expect(mount(typeToolbarAction.getNode()).instance().props.open).toBeTruthy();

    mount(typeToolbarAction.getNode()).instance().props.onCancel();
    expect(typeToolbarAction.resourceFormStore.changeType).not.toBeCalled();
    expect(mount(typeToolbarAction.getNode()).instance().props.open).toBeFalsy();
});

test('Throw error if no types are available in FormStore', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.resourceFormStore.typesLoading = false;
    typeToolbarAction.resourceFormStore.types = {};

    expect(() => typeToolbarAction.getToolbarItemConfig()).toThrow(/actually supporting types/);
});

test('Return disabled true when passed disabled condition is not met', () => {
    const typeToolbarAction = createTypeToolbarAction({disabled_condition: 'url == "/"'});

    expect(typeToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({disabled: false}));
});

test('Return disabled true when passed disabled condition is met', () => {
    const typeToolbarAction = createTypeToolbarAction({disabled_condition: 'url == "/"'});
    typeToolbarAction.resourceFormStore.data.url = '/';

    expect(typeToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({disabled: true}));
});
