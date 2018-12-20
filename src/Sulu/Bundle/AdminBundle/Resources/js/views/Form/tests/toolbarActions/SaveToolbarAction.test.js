// @flow
import SaveToolbarAction from '../../toolbarActions/SaveToolbarAction';
import {FormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function() {}));

jest.mock('../../../../containers/Form', () => ({
    FormStore: class {
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
    },
}));

jest.mock('../../../../services/Router', () => jest.fn(function() {}));

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
}));

function createSaveToolbarAction() {
    const resourceStore = new ResourceStore('test');
    const formStore = new FormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new SaveToolbarAction(formStore, form, router);
}

test('Return item config with correct disabled, loading, icon, type and value', () => {
    const saveToolbarAction = createSaveToolbarAction();
    saveToolbarAction.formStore.resourceStore.saving = false;

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
    saveToolbarAction.formStore.resourceStore.dirty = true;

    expect(saveToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
    }));
});

test('Return item config with loading button when saving flag is set', () => {
    const saveToolbarAction = createSaveToolbarAction();
    saveToolbarAction.formStore.resourceStore.saving = true;

    expect(saveToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        loading: true,
    }));
});

test('Submit form when button is clicked', () => {
    const saveToolbarAction = createSaveToolbarAction();
    const toolbarItemConfig = saveToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    expect(saveToolbarAction.form.submit).toBeCalledWith();
});
