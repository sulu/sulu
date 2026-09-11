// @flow
import {mount} from 'enzyme';
import symfonyRouting from 'fos-jsrouting/router';
import SuggestFormStoreToolbarAction from '../../toolbarActions/SuggestFormStoreToolbarAction';
import {setAccountLimitContactEmail} from '../../../../containers/AiApplication/accountLimits';
import {ResourceFormStore} from '../../../../containers/Form';
import memoryFormStoreFactory from '../../../../containers/Form/stores/memoryFormStoreFactory';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import Requester from '../../../../services/Requester';

jest.mock('../../../../services/Requester', () => ({
    post: jest.fn(),
}));

jest.mock('fos-jsrouting/router', () => ({
    generate: jest.fn(),
}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../containers/Form/stores/metadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
}));

jest.mock('../../../../containers/Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn((formKey, data) => ({
        data: {...(data || {})},
        errors: [],
        schema: {},
        types: {},
        validate: jest.fn().mockReturnValue(true),
        destroy: jest.fn(),
        // a plain method (not an arrow function) so "this" resolves to whatever object mobx
        // ends up exposing as the observable once assigned to an "@observable" class field,
        // rather than closing over this factory call's own (possibly stale) object reference
        changeMultiple: jest.fn(function(values) {
            this.data = {...this.data, ...values};
        }),
    })),
}));

jest.mock('../../../../containers/Form/stores/ResourceFormStore', () => (
    class {
        resourceStore;
        options = {};

        setMultiple = jest.fn();

        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        get id() {
            return this.resourceStore.id;
        }

        get data() {
            return this.resourceStore.data;
        }

        get locale() {
            return this.resourceStore.locale;
        }
    }
));

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
    this.route = {
        options: {},
    };
}));

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
    this.showSuccessSnackbar = jest.fn();
    this.errors = require('mobx').observable([]);
    this.warnings = require('mobx').observable([]);
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.id = id;
    this.data = {};
    this.observableOptions = observableOptions;
    this.locale = {
        get: jest.fn(),
    };
}));

function createSuggestFormStoreToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });
    return new SuggestFormStoreToolbarAction(
        resourceFormStore,
        form,
        router,
        [],
        {
            icon: 'su-magic',
            route: 'test_route',
            contentExpressions: [{property: 'title', get: 'title', path: '/title'}],
            dialogKey: 'test_dialog',
            dialogTitle: 'Test Dialog',
            dialogDescription: 'Test Description',
            dialogCancelText: 'Cancel',
            dialogOkText: 'Generate',
            dialogInsertText: 'Insert',
            regenerateText: 'Regenerate',
            label: 'Test Dialog',
            suggestionFormKey: 'test_suggestion_form',
            ...options,
        },
        resourceStore
    );
}

test('Throw error if required options from the shared base are missing', () => {
    expect(() => createSuggestFormStoreToolbarAction({icon: undefined}))
        .toThrow(/Missing required options/);
});

test('Throw error if suggestionFormKey option is missing', () => {
    expect(() => createSuggestFormStoreToolbarAction({suggestionFormKey: undefined}))
        .toThrow(/suggestionFormKey/);
});

test('Generate and apply directly when no content exists', async() => {
    const action = createSuggestFormStoreToolbarAction();
    action.resourceFormStore.resourceStore.data = {};
    // $FlowFixMe
    action.resourceFormStore.change = jest.fn();

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en');
    Requester.post.mockResolvedValue({title: 'Generated title'});

    const config = action.getToolbarItemConfig();
    await config.onClick();
    await new Promise((resolve) => setTimeout(resolve));

    expect(action.showDialog).toBe(false);
    expect(action.resourceFormStore.change).toHaveBeenCalledWith('/title', 'Generated title');
    expect(action.form.showSuccessSnackbar).toHaveBeenCalled();
});

test('Open the dialog and start generating immediately when content exists, with optimize defaulted on', async() => {
    const action = createSuggestFormStoreToolbarAction({formKey: 'test_options_form'});
    action.resourceFormStore.resourceStore.id = 5;
    action.resourceFormStore.resourceStore.data = {title: 'Existing title'};
    // $FlowFixMe
    action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en');
    Requester.post.mockResolvedValue({title: 'Suggested title'});

    const config = action.getToolbarItemConfig();
    await config.onClick();

    expect(action.showDialog).toBe(true);
    // $FlowFixMe
    expect(action.originalFormStore.data).toEqual({title: 'Existing title'});
    expect(memoryFormStoreFactory.createFromFormKey).toHaveBeenCalledWith(
        'test_options_form',
        {optimize: true},
        undefined,
        undefined,
        undefined
    );

    await new Promise((resolve) => setTimeout(resolve));

    expect(Requester.post).toHaveBeenCalledWith('/test/5?locale=en', {
        content: {title: 'Existing title'},
        data: {optimize: true},
    });
    // $FlowFixMe
    expect(action.suggestionFormStore.data).toEqual({title: 'Suggested title'});
    expect(action.resourceFormStore.data.title).toBe('Existing title');
});

test('Insert writes the suggestion into the resource form store, closes, and keeps its content intact', async() => {
    const action = createSuggestFormStoreToolbarAction();
    action.resourceFormStore.resourceStore.data = {title: 'Existing title'};
    // $FlowFixMe
    action.resourceFormStore.change = jest.fn();
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'Existing title'});
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'Suggested title'});

    const element = mount(action.getNode());
    element.find('Button[skin="primary"]').simulate('click');

    expect(action.resourceFormStore.change).toHaveBeenCalledWith('/title', 'Suggested title');
    expect(action.form.showSuccessSnackbar).toHaveBeenCalled();
    expect(action.showDialog).toBe(false);
    // left alone on purpose, same reasoning as the Cancel case: the Dialog is still fading out
    // and would otherwise repaint mid-transition into an empty, disabled-looking state
    // $FlowFixMe
    expect(action.suggestionFormStore.data).toEqual({title: 'Suggested title'});
    // $FlowFixMe
    expect(action.originalFormStore.data).toEqual({title: 'Existing title'});
});

test('Regenerate re-fetches without closing the dialog', async() => {
    const action = createSuggestFormStoreToolbarAction();
    action.resourceFormStore.resourceStore.id = 5;
    action.resourceFormStore.resourceStore.data = {title: 'Existing title'};
    // $FlowFixMe
    action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'Existing title'});
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'First suggestion'});

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en');
    Requester.post.mockResolvedValue({title: 'Second suggestion'});

    const element = mount(action.getNode());
    element.find('Button[icon="su-magic"]').simulate('click');

    await new Promise((resolve) => setTimeout(resolve));

    expect(action.showDialog).toBe(true);
    // $FlowFixMe
    expect(action.suggestionFormStore.data).toEqual({title: 'Second suggestion'});
});

test('Regenerate blanks the suggestion fields while the new response is in flight', async() => {
    const action = createSuggestFormStoreToolbarAction();
    action.resourceFormStore.resourceStore.id = 5;
    action.resourceFormStore.resourceStore.data = {title: 'Existing title'};
    // $FlowFixMe
    action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'Existing title'});
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'First suggestion'});

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en');
    let resolveRequest;
    Requester.post.mockReturnValue(new Promise((resolve) => { resolveRequest = resolve; }));

    const element = mount(action.getNode());
    element.find('Button[icon="su-magic"]').simulate('click');

    // $FlowFixMe
    expect(action.suggestionFormStore.data).toEqual({title: undefined});

    resolveRequest({title: 'Second suggestion'});
    await new Promise((resolve) => setTimeout(resolve));

    // $FlowFixMe
    expect(action.suggestionFormStore.data).toEqual({title: 'Second suggestion'});
});

test('Temporary error while generating keeps the dialog open with a snackbar', async() => {
    const action = createSuggestFormStoreToolbarAction();
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'Existing title'});
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'First suggestion'});

    const error = new Error('Test Error');
    // $FlowFixMe
    error.json = jest.fn().mockResolvedValue({messageKey: 'sulu_ai.ai_request_failed'});
    Requester.post.mockRejectedValue(error);

    const element = mount(action.getNode());
    element.find('Button[icon="su-magic"]').simulate('click');

    await new Promise((resolve) => setTimeout(resolve));

    expect(action.showDialog).toBe(true);
    expect(action.dialogSnackbarMessage).toBe('sulu_admin.request_failed');
    expect(action.form.errors).toHaveLength(0);
    expect(action.form.warnings).toHaveLength(0);
});

test('Account limit error while generating closes the dialog with a terminal error', async() => {
    setAccountLimitContactEmail('admin@example.com');

    const action = createSuggestFormStoreToolbarAction();
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'Existing title'});
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'First suggestion'});

    const error = new Error('Test Error');
    // $FlowFixMe
    error.json = jest.fn().mockResolvedValue({messageKey: 'sulu_ai.out_of_credits'});
    Requester.post.mockRejectedValue(error);

    const element = mount(action.getNode());
    element.find('Button[icon="su-magic"]').simulate('click');

    await new Promise((resolve) => setTimeout(resolve));

    expect(action.showDialog).toBe(false);
    const lastError = action.form.errors[action.form.errors.length - 1];
    expect(lastError).toEqual(expect.objectContaining({
        title: 'sulu_ai.out_of_credits',
        message: 'sulu_ai.out_of_credits_description',
    }));

    setAccountLimitContactEmail(undefined);
});

test('Cancel closes the dialog without clearing its content mid-transition', () => {
    const action = createSuggestFormStoreToolbarAction();
    action.showDialog = true;
    const originalFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'Existing title'});
    action.originalFormStore = originalFormStore;
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'Suggestion'});

    const element = mount(action.getNode());
    element.find('Button[skin="secondary"]').filterWhere((button) => 'Cancel' === button.text()).simulate('click');

    expect(action.showDialog).toBe(false);
    // the stores are left alone here on purpose: the Dialog component keeps rendering its
    // "children" prop until its own CSS close transition ends, so clearing them at this point
    // would repaint the still-visible dialog into an empty, disabled state mid-fade
    // $FlowFixMe
    expect(action.originalFormStore.data).toEqual({title: 'Existing title'});
    expect(originalFormStore.destroy).not.toHaveBeenCalled();
});

test('Opening the dialog again destroys stores left over from the previous session', async() => {
    const action = createSuggestFormStoreToolbarAction();
    action.resourceFormStore.resourceStore.id = 5;
    action.resourceFormStore.resourceStore.data = {title: 'Existing title'};
    // $FlowFixMe
    action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');

    const staleOriginalFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'Stale'});
    const staleSuggestionFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {title: 'Stale suggestion'});
    action.originalFormStore = staleOriginalFormStore;
    action.suggestionFormStore = staleSuggestionFormStore;

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en');
    Requester.post.mockResolvedValue({title: 'Suggested title'});

    const config = action.getToolbarItemConfig();
    await config.onClick();

    expect(staleOriginalFormStore.destroy).toHaveBeenCalled();
    expect(staleSuggestionFormStore.destroy).toHaveBeenCalled();
    // $FlowFixMe
    expect(action.originalFormStore.data).toEqual({title: 'Existing title'});
});
