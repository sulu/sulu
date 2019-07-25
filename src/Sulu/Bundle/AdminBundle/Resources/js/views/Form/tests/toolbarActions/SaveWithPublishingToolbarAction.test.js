// @flow
import SaveWithPublishingToolbarAction from '../../toolbarActions/SaveWithPublishingToolbarAction';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function() {
    this.data = {};
}));

jest.mock('../../../../containers/Form', () => ({
    ResourceFormStore: class {
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

jest.mock('../../../../services/Router', () => jest.fn());

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
}));

function createSaveWithPublishingToolbarAction() {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new SaveWithPublishingToolbarAction(resourceFormStore, form, router);
}

test('Return item config with correct disabled, loading, icon, type and value', () => {
    const publishableSaveToolbarAction = createSaveWithPublishingToolbarAction();
    publishableSaveToolbarAction.resourceFormStore.resourceStore.saving = false;
    publishableSaveToolbarAction.resourceFormStore.resourceStore.dirty = false;
    publishableSaveToolbarAction.resourceFormStore.resourceStore.data.publishedState = true;

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
    publishableSaveToolbarAction.resourceFormStore.resourceStore.dirty = true;

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
    publishableSaveToolbarAction.resourceFormStore.resourceStore.dirty = false;
    publishableSaveToolbarAction.resourceFormStore.resourceStore.data.publishedState = false;

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

test('Return item config with all options disabled when not dirty and data was not loaded yet', () => {
    const publishableSaveToolbarAction = createSaveWithPublishingToolbarAction();
    publishableSaveToolbarAction.resourceFormStore.resourceStore.dirty = false;
    publishableSaveToolbarAction.resourceFormStore.resourceStore.data = {};

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
                disabled: true,
                label: 'sulu_admin.publish',
            }),
        ],
    }));
});

test('Return item config with loading button when saving flag is set', () => {
    const publishableSaveToolbarAction = createSaveWithPublishingToolbarAction();
    publishableSaveToolbarAction.resourceFormStore.resourceStore.saving = true;

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
