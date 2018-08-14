// @flow
import SaveToolbarAction from '../../toolbarActions/SaveToolbarAction';
import Form, {FormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function() {}));

jest.mock('../../../../containers/Form', () => ({
    __esModule: true,
    default: jest.fn(function() {
        this.submit = jest.fn();
    }),
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

function createSaveToolbarAction() {
    const formStore = new FormStore(new ResourceStore('test'));
    const form = new Form({
        onSubmit: jest.fn(),
        store: formStore,
    });
    const router = new Router({});
    return new SaveToolbarAction(formStore, form, router);
}

test('Return item config with correct disabled, loading, icon, type and value', () => {
    const saveToolbarAction = createSaveToolbarAction();
    saveToolbarAction.formStore.resourceStore.saving = false;

    expect(saveToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
        loading: false,
        icon: 'su-save',
        type: 'button',
        value: 'sulu_admin.save',
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
