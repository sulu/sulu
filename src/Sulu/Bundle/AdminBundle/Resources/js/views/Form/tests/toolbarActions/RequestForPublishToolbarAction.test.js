// @flow
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import RequestForPublishToolbarAction from '../../toolbarActions/RequestForPublishToolbarAction';
import {setRequestWorkflowTemplates} from '../../requestWorkflowConfig';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey) {
    this.data = {};
    this.resourceKey = resourceKey;
}));

beforeEach(() => {
    setRequestWorkflowTemplates({test: ['covered']});
});

jest.mock('../../../../containers/Form/stores/ResourceFormStore', () => (
    class {
        resourceStore;
        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        get dirty() {
            return this.resourceStore.dirty;
        }

        get data() {
            return this.resourceStore.data;
        }

        get id() {
            return this.resourceStore.id;
        }

        get resourceKey() {
            return this.resourceStore.resourceKey;
        }
    }
));

jest.mock('../../../../services/Router', () => jest.fn());

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
}));

function createRequestForPublishToolbarAction(options = {}, id = 'saved-id') {
    const resourceStore = new ResourceStore('test');
    resourceStore.id = id;
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new RequestForPublishToolbarAction(resourceFormStore, form, router, [], options, resourceStore);
}

test.each([
    ['unpublished', 'request_for_review'],
    ['draft', 'request_for_review_draft'],
    ['published', 'request_for_review_draft'],
])('Submit the "%s" workflow place with the "%s" transition', (workflowPlace, transition) => {
    const toolbarAction = createRequestForPublishToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.workflowTransitionRequestEnabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.workflowPlace = workflowPlace;
    toolbarAction.resourceFormStore.resourceStore.dirty = true;

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();

    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    toolbarItemConfig.onClick();

    expect(toolbarAction.form.submit).toHaveBeenCalledWith({action: transition});
});

test.each([
    ['review'],
    ['review_draft'],
])('Return no item config for the "%s" workflow place, which has no request transition', (workflowPlace) => {
    const toolbarAction = createRequestForPublishToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.workflowTransitionRequestEnabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.workflowPlace = workflowPlace;

    expect(toolbarAction.getToolbarItemConfig()).toBeUndefined();
});

test('Request the review of what the create form will save, which is unpublished and has no place yet', () => {
    const toolbarAction = createRequestForPublishToolbarAction({}, null);
    toolbarAction.resourceFormStore.resourceStore.data.template = 'covered';

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();

    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    expect(toolbarItemConfig).toEqual(expect.objectContaining({disabled: false}));

    toolbarItemConfig.onClick();

    expect(toolbarAction.form.submit).toHaveBeenCalledWith({action: 'request_for_review'});
});

test('Return item config with a disabled button for a published page without changes', () => {
    const toolbarAction = createRequestForPublishToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.workflowTransitionRequestEnabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.workflowPlace = 'published';
    toolbarAction.resourceFormStore.resourceStore.dirty = false;

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({disabled: true}));
});

test('Return item config with an enabled button for a published page with changes', () => {
    const toolbarAction = createRequestForPublishToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.workflowTransitionRequestEnabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.workflowPlace = 'published';
    toolbarAction.resourceFormStore.resourceStore.dirty = true;

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({disabled: false}));
});

test('Return item config with an enabled button while the form is dirty, because the action saves first', () => {
    const toolbarAction = createRequestForPublishToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.workflowTransitionRequestEnabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.workflowPlace = 'draft';
    toolbarAction.resourceFormStore.resourceStore.dirty = true;

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({disabled: false}));
});

test('Return item config with disabled button if passed disabled_condition is met', () => {
    const toolbarAction = createRequestForPublishToolbarAction({disabled_condition: 'title == nil'});
    toolbarAction.resourceFormStore.resourceStore.data.workflowTransitionRequestEnabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.workflowPlace = 'draft';

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({disabled: true}));
});

test('Return no item config if passed visible_condition is not met', () => {
    const toolbarAction = createRequestForPublishToolbarAction({visible_condition: '_permissions.edit'});
    toolbarAction.resourceFormStore.resourceStore.data.workflowTransitionRequestEnabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.workflowPlace = 'draft';
    toolbarAction.resourceFormStore.resourceStore.data._permissions = {edit: false};

    expect(toolbarAction.getToolbarItemConfig()).toBeUndefined();
});

test('Offer nothing on the create form when no workflow covers the picked template', () => {
    const toolbarAction = createRequestForPublishToolbarAction({}, null);
    toolbarAction.resourceFormStore.resourceStore.data.template = 'not-covered';

    expect(toolbarAction.getToolbarItemConfig()).toBeUndefined();
});

test('Offer nothing for saved content that no workflow covers', () => {
    const toolbarAction = createRequestForPublishToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.workflowPlace = 'draft';

    expect(toolbarAction.getToolbarItemConfig()).toBeUndefined();
});
