// @flow
import SaveWithPublishingToolbarAction from '../../toolbarActions/SaveWithPublishingToolbarAction';
import Form, {FormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function() {
    this.data = {};
}));

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

        get data() {
            return this.resourceStore.data;
        }
    },
}));

jest.mock('../../../../services/Router', () => jest.fn(function() {}));

function createSaveWithPublishingToolbarAction() {
    const formStore = new FormStore(new ResourceStore('test'));
    const form = new Form({
        onSubmit: jest.fn(),
        store: formStore,
    });
    const router = new Router({});
    return new SaveWithPublishingToolbarAction(formStore, form, router);
}

test('Return item config with correct disabled, loading, icon, type and value', () => {
    const publishableSaveToolbarAction = createSaveWithPublishingToolbarAction();
    publishableSaveToolbarAction.formStore.resourceStore.saving = false;
    publishableSaveToolbarAction.formStore.resourceStore.dirty = false;
    publishableSaveToolbarAction.formStore.resourceStore.data.publishedState = true;

    expect(publishableSaveToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        label: 'sulu_admin.save',
        loading: false,
        options: [
            expect.objectContaining({
                disabled: true,
                label: 'sulu_admin.save_draft',
            }),
            expect.objectContaining({
                disabled: true,
                label: 'sulu_admin.save_publish',
            }),
            expect.objectContaining({
                disabled: true,
                label: 'sulu_admin.publish',
            }),
        ],
        icon: 'su-save',
        type: 'dropdown',
    }));
});

test('Return item config with enabled draft and save & publish option when dirty flag is set', () => {
    const publishableSaveToolbarAction = createSaveWithPublishingToolbarAction();
    publishableSaveToolbarAction.formStore.resourceStore.dirty = true;

    expect(publishableSaveToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        options: [
            expect.objectContaining({
                disabled: false,
                label: 'sulu_admin.save_draft',
            }),
            expect.objectContaining({
                disabled: false,
                label: 'sulu_admin.save_publish',
            }),
            expect.objectContaining({
                disabled: true,
                label: 'sulu_admin.publish',
            }),
        ],
    }));
});

test('Return item config with publish option when not dirty but unpublished', () => {
    const publishableSaveToolbarAction = createSaveWithPublishingToolbarAction();
    publishableSaveToolbarAction.formStore.resourceStore.dirty = false;
    publishableSaveToolbarAction.formStore.resourceStore.data.publishedState = false;

    expect(publishableSaveToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        options: [
            expect.objectContaining({
                disabled: true,
                label: 'sulu_admin.save_draft',
            }),
            expect.objectContaining({
                disabled: true,
                label: 'sulu_admin.save_publish',
            }),
            expect.objectContaining({
                disabled: false,
                label: 'sulu_admin.publish',
            }),
        ],
    }));
});

test('Return item config with loading button when saving flag is set', () => {
    const publishableSaveToolbarAction = createSaveWithPublishingToolbarAction();
    publishableSaveToolbarAction.formStore.resourceStore.saving = true;

    expect(publishableSaveToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        loading: true,
    }));
});

test('Submit form with draft action when draft option is clicked', () => {
    const publishableSaveToolbarAction = createSaveWithPublishingToolbarAction();
    const toolbarItemConfig = publishableSaveToolbarAction.getToolbarItemConfig();

    if (!toolbarItemConfig.options[0].onClick) {
        throw new Error('The option must define a onClick callback!');
    }

    toolbarItemConfig.options[0].onClick();

    expect(publishableSaveToolbarAction.form.submit).toBeCalledWith('draft');
});

test('Submit form with publish action when draft option is clicked', () => {
    const publishableSaveToolbarAction = createSaveWithPublishingToolbarAction();
    const toolbarItemConfig = publishableSaveToolbarAction.getToolbarItemConfig();

    if (!toolbarItemConfig.options[1].onClick) {
        throw new Error('The option must define a onClick callback!');
    }

    toolbarItemConfig.options[1].onClick();

    expect(publishableSaveToolbarAction.form.submit).toBeCalledWith('publish');
});
