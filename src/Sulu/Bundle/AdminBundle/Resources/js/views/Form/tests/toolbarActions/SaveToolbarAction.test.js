// @flow
import SaveToolbarAction from '../../toolbarActions/SaveToolbarAction';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import conditionDataProviderRegistry from '../../../../containers/Form/registries/conditionDataProviderRegistry';

beforeEach(() => {
    conditionDataProviderRegistry.clear();
});

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function() {
    this.data = {};
}));
jest.mock('../../../../containers/Form/stores/ResourceFormStore', () => (
    class {
        resourceStore;
        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        get dirty() {
            return this.resourceStore.dirty;
        }

        get saving() {
            return this.resourceStore.saving;
        }

        get data() {
            return this.resourceStore.data;
        }
    }
));
jest.mock('../../../../services/Router', () => jest.fn());

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
}));

function createSaveToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new SaveToolbarAction(resourceFormStore, form, router, [], options, resourceStore);
}

test('Return item config with correct disabled, loading, icon, type and value', () => {
    const saveToolbarAction = createSaveToolbarAction();
    saveToolbarAction.resourceFormStore.resourceStore.saving = false;

    expect(saveToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
        label: 'sulu_admin.save',
        loading: false,
        icon: 'su-save',
        type: 'button',
    }));
});

test('Return item config with enabled button when dirty flag is set', () => {
    const saveToolbarAction = createSaveToolbarAction();
    saveToolbarAction.resourceFormStore.resourceStore.dirty = true;

    expect(saveToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
    }));
});

test('Return item config with loading button when saving flag is set', () => {
    const saveToolbarAction = createSaveToolbarAction();
    saveToolbarAction.resourceFormStore.resourceStore.saving = true;

    expect(saveToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        loading: true,
    }));
});

test('Return item config if passed visible_condition is met', () => {
    const saveToolbarAction = createSaveToolbarAction({visible_condition: '_permission.edit'});
    saveToolbarAction.resourceFormStore.resourceStore.data._permission = {edit: true};

    expect(saveToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        label: 'sulu_admin.save',
    }));
});

test('Return empty item config if passed visible_condition is not met', () => {
    const saveToolbarAction = createSaveToolbarAction({visible_condition: '_permission.edit'});
    saveToolbarAction.resourceFormStore.resourceStore.data._permission = {edit: false};

    expect(saveToolbarAction.getToolbarItemConfig()).toEqual(undefined);
});

test('Include data of conditionDataProviderRegistry when evaluating passed visible_condition', () => {
    const saveToolbarAction = createSaveToolbarAction({visible_condition: '__conditionDataProviderValue'});
    expect(saveToolbarAction.getToolbarItemConfig()).toBeUndefined();

    conditionDataProviderRegistry.add(() => ({__conditionDataProviderValue: true}));
    expect(saveToolbarAction.getToolbarItemConfig()).toBeDefined();

    conditionDataProviderRegistry.clear();
    conditionDataProviderRegistry.add(() => ({__conditionDataProviderValue: false}));
    expect(saveToolbarAction.getToolbarItemConfig()).toBeUndefined();
});

test('Return item config with label from options', () => {
    const saveToolbarAction = createSaveToolbarAction({label: 'app.custom_save_text'});

    expect(saveToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        label: 'app.custom_save_text',
    }));
});

test('Throw error if given label is not a string', () => {
    const saveToolbarAction = createSaveToolbarAction({label: {}});

    expect(() => saveToolbarAction.getToolbarItemConfig()).toThrow(/"label" option must be a string/);
});

test('Submit form when button is clicked', () => {
    const saveToolbarAction = createSaveToolbarAction();
    const toolbarItemConfig = saveToolbarAction.getToolbarItemConfig();

    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    toolbarItemConfig.onClick();

    expect(saveToolbarAction.form.submit).toBeCalledWith(undefined);
});

test('Submit form with given options when button is clicked', () => {
    const saveToolbarAction = createSaveToolbarAction({options: {action: 'publish'}});
    const toolbarItemConfig = saveToolbarAction.getToolbarItemConfig();

    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    toolbarItemConfig.onClick();

    expect(saveToolbarAction.form.submit).toBeCalledWith({action: 'publish'});
});

test('Throw error if given options are not an object', () => {
    const saveToolbarAction = createSaveToolbarAction({options: 'test'});

    expect(() => saveToolbarAction.getToolbarItemConfig()).toThrow(/"options" option must be an object/);
});
