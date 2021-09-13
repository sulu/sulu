// @flow
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import PublishToolbarAction from '../../toolbarActions/PublishToolbarAction';

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

function createPublishToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new PublishToolbarAction(resourceFormStore, form, router, [], options, resourceStore);
}

test('Return item config with correct label, disabled, and type', () => {
    const publishToolbarAction = createPublishToolbarAction();
    publishToolbarAction.resourceFormStore.resourceStore.dirty = false;
    publishToolbarAction.resourceFormStore.resourceStore.data.publishedState = true;

    expect(publishToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        label: 'sulu_admin.publish',
        disabled: true,
        type: 'button',
    }));
});

test('Return item config with enabled button when resource is not published and form is not dirty', () => {
    const publishToolbarAction = createPublishToolbarAction();
    publishToolbarAction.resourceFormStore.resourceStore.dirty = false;
    publishToolbarAction.resourceFormStore.resourceStore.data.publishedState = false;

    expect(publishToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
    }));
});

test('Return item config with disabled button when resource is not published but form is not dirty', () => {
    const publishToolbarAction = createPublishToolbarAction();
    publishToolbarAction.resourceFormStore.resourceStore.dirty = true;
    publishToolbarAction.resourceFormStore.resourceStore.data.publishedState = false;

    expect(publishToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
    }));
});

test('Return item config with disabled button when resource is not loaded yet', () => {
    const publishToolbarAction = createPublishToolbarAction();
    publishToolbarAction.resourceFormStore.resourceStore.dirty = false;
    publishToolbarAction.resourceFormStore.resourceStore.data = {};

    expect(publishToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
    }));
});

test('Return item config if passed visible_condition is met', () => {
    const publishToolbarAction = createPublishToolbarAction({visible_condition: '_permission.live'});
    publishToolbarAction.resourceFormStore.resourceStore.data._permission = {live: true};

    expect(publishToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        label: 'sulu_admin.publish',
    }));
});

test('Return empty item config if passed visible_condition is not met', () => {
    const publishToolbarAction = createPublishToolbarAction({visible_condition: '_permission.live'});
    publishToolbarAction.resourceFormStore.resourceStore.data._permission = {live: false};

    expect(publishToolbarAction.getToolbarItemConfig()).toEqual(undefined);
});

test('Submit form with correct options when button is clicked', () => {
    const publishToolbarAction = createPublishToolbarAction();
    const toolbarItemConfig = publishToolbarAction.getToolbarItemConfig();

    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    toolbarItemConfig.onClick();

    expect(publishToolbarAction.form.submit).toBeCalledWith({action: 'publish'});
});
