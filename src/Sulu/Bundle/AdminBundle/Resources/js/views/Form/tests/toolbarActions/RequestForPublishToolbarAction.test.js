// @flow
import {ResourceFormStore} from '../../../../containers/Form';
import memoryFormStoreFactory from '../../../../containers/Form/stores/memoryFormStoreFactory';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import RequestForPublishToolbarAction from '../../toolbarActions/RequestForPublishToolbarAction';

jest.mock('../../../../containers/Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(() => ({
        data: {},
        loading: false,
        schema: {},
    })),
}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey) {
    this.data = {};
    this.resourceKey = resourceKey;
}));

jest.mock('../../../../containers/Form/stores/ResourceFormStore', () => (
    class {
        resourceStore;
        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        change = jest.fn();

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

    expect(toolbarAction.resourceFormStore.change).toHaveBeenCalledWith('workflowTransitionRequestData', {});
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
    const toolbarAction = createRequestForPublishToolbarAction({templates: ['covered']}, null);
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
    const toolbarAction = createRequestForPublishToolbarAction({templates: ['covered']}, null);
    toolbarAction.resourceFormStore.resourceStore.data.template = 'not-covered';

    expect(toolbarAction.getToolbarItemConfig()).toBeUndefined();
});

test('Offer nothing for saved content that no workflow covers', () => {
    const toolbarAction = createRequestForPublishToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.workflowPlace = 'draft';

    expect(toolbarAction.getToolbarItemConfig()).toBeUndefined();
});

test('Open the overlay instead of submitting when the request form has fields', () => {
    memoryFormStoreFactory.createFromFormKey.mockReturnValueOnce({
        data: {},
        loading: false,
        schema: {publishAt: {}},
    });

    const toolbarAction = createRequestForPublishToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.workflowTransitionRequestEnabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.workflowPlace = 'draft';

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();

    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    toolbarItemConfig.onClick();

    expect(toolbarAction.open).toBe(true);
    expect(toolbarAction.form.submit).not.toHaveBeenCalled();
});

test('Submit the transition with the data of the request form when the overlay is confirmed', () => {
    memoryFormStoreFactory.createFromFormKey.mockReturnValueOnce({
        data: {publishAt: '2026-12-01T10:00:00+00:00'},
        loading: false,
        schema: {publishAt: {}},
    });

    const toolbarAction = createRequestForPublishToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.workflowTransitionRequestEnabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.workflowPlace = 'draft';

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();

    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    toolbarItemConfig.onClick();
    toolbarAction.handleConfirm();

    expect(toolbarAction.open).toBe(false);
    expect(toolbarAction.resourceFormStore.change).toHaveBeenCalledWith(
        'workflowTransitionRequestData',
        {publishAt: '2026-12-01T10:00:00+00:00'}
    );
    expect(toolbarAction.form.submit).toHaveBeenCalledWith({action: 'request_for_review_draft'});
});

test('Submit nothing when the overlay is closed', () => {
    memoryFormStoreFactory.createFromFormKey.mockReturnValueOnce({
        data: {},
        loading: false,
        schema: {publishAt: {}},
    });

    const toolbarAction = createRequestForPublishToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.workflowTransitionRequestEnabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.workflowPlace = 'draft';

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();

    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    toolbarItemConfig.onClick();
    toolbarAction.handleClose();

    expect(toolbarAction.open).toBe(false);
    expect(toolbarAction.form.submit).not.toHaveBeenCalled();
});

function createActionWithRequestForm(data) {
    memoryFormStoreFactory.createFromFormKey.mockReturnValueOnce({data, loading: false, schema: {message: {}}});

    const toolbarAction = createRequestForPublishToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.workflowTransitionRequestEnabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.workflowPlace = 'draft';

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();

    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    toolbarItemConfig.onClick();

    return toolbarAction;
}

test('Keep the entered values while the save is running and when it fails', async() => {
    const toolbarAction = createActionWithRequestForm({message: 'Please check'});
    (toolbarAction.form.submit: any).mockReturnValue(Promise.resolve(undefined));

    toolbarAction.handleConfirm();
    await Promise.resolve();

    expect(toolbarAction.open).toBe(false);
    expect(toolbarAction.requestFormStore.data).toEqual({message: 'Please check'});
});

test('Keep the entered values when the form refuses to submit', () => {
    const toolbarAction = createActionWithRequestForm({message: 'Please check'});
    (toolbarAction.form.submit: any).mockReturnValue(undefined);

    toolbarAction.handleConfirm();

    expect(toolbarAction.requestFormStore.data).toEqual({message: 'Please check'});
});

test('Start with an empty request form after the save succeeded', async() => {
    const toolbarAction = createActionWithRequestForm({message: 'Please check'});
    (toolbarAction.form.submit: any).mockReturnValue(Promise.resolve({id: 'saved-id'}));

    toolbarAction.handleConfirm();
    await Promise.resolve();
    await Promise.resolve();

    expect(toolbarAction.requestFormStore.data).toEqual({});
});

test('Drop the entered values when the overlay is closed without confirming', () => {
    const toolbarAction = createActionWithRequestForm({message: 'Please check'});

    toolbarAction.handleClose();

    expect(toolbarAction.requestFormStore.data).toEqual({});
});
