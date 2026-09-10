// @flow
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import AbstractFormToolbarAction from '../../toolbarActions/AbstractFormToolbarAction';

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

        get data() {
            return this.resourceStore.data;
        }

        get locked() {
            return !!this.resourceStore.data._locked;
        }
    }
));

jest.mock('../../../../services/Router', () => jest.fn());

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
}));

class TestToolbarAction extends AbstractFormToolbarAction {
    getToolbarItemConfig() {
        return {
            label: 'test',
            onClick: jest.fn(),
            type: 'button',
        };
    }
}

class UnlockableToolbarAction extends TestToolbarAction {
    get enabledWhileLocked() {
        return true;
    }
}

class HiddenToolbarAction extends AbstractFormToolbarAction {
    getToolbarItemConfig() {
        return undefined;
    }
}

function create(ToolbarAction) {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new ToolbarAction(resourceFormStore, form, router, [], {}, resourceStore);
}

test('Item config is untouched when the form is not locked', () => {
    const toolbarAction = create(TestToolbarAction);
    const config = toolbarAction.getLockAwareToolbarItemConfig();

    if (!config) {
        throw new Error('Expected a toolbar item config');
    }

    expect(config.label).toBe('test');
    expect(config.disabled).toBeUndefined();
});

test('Item config is disabled when the form is locked', () => {
    const toolbarAction = create(TestToolbarAction);
    toolbarAction.resourceFormStore.resourceStore.data._locked = true;

    expect(toolbarAction.getLockAwareToolbarItemConfig()).toEqual(
        expect.objectContaining({label: 'test', disabled: true})
    );
});

test('Actions opting out of the lock stay enabled', () => {
    const toolbarAction = create(UnlockableToolbarAction);
    toolbarAction.resourceFormStore.resourceStore.data._locked = true;
    const config = toolbarAction.getLockAwareToolbarItemConfig();

    if (!config) {
        throw new Error('Expected a toolbar item config');
    }

    expect(config.label).toBe('test');
    expect(config.disabled).toBeUndefined();
});

test('Hidden actions stay hidden when the form is locked', () => {
    const toolbarAction = create(HiddenToolbarAction);
    toolbarAction.resourceFormStore.resourceStore.data._locked = true;

    expect(toolbarAction.getLockAwareToolbarItemConfig()).toBeUndefined();
});

test('Actions are enabled by default', () => {
    expect(create(TestToolbarAction).enabledWhileLocked).toBe(false);
});
