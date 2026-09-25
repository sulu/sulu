// @flow
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import symfonyRouting from 'fos-jsrouting/router';
import SuggestFormStoreToolbarAction from '../../toolbarActions/SuggestFormStoreToolbarAction';
import {setAccountLimitContactEmail} from '../../../../containers/AiApplication/accountLimits';
import {ResourceFormStore} from '../../../../containers/Form';
import memoryFormStoreFactory from '../../../../containers/Form/stores/memoryFormStoreFactory';
import metadataStore from '../../../../containers/Form/stores/metadataStore';
import SchemaFormStoreDecorator from '../../../../containers/Form/stores/SchemaFormStoreDecorator';
import MemoryFormStore from '../../../../containers/Form/stores/MemoryFormStore';
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
        loading: false,
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
    await waitFor(() => expect(action.resourceFormStore.change)
        .toHaveBeenCalledWith('/title', 'Generated title'));

    expect(action.showDialog).toBe(false);
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

    await waitFor(() => expect(Requester.post).toHaveBeenCalledWith('/test/5?locale=en', {
        content: {title: 'Existing title'},
        data: {optimize: true},
    }));
    // $FlowFixMe
    expect(action.suggestionFormStore.data).toEqual({title: 'Suggested title'});
    expect(action.resourceFormStore.data.title).toBe('Existing title');
});

test('Insert writes the suggestion into the resource form store, closes, and keeps its content intact', async() => {
    const user = userEvent.setup();
    const action = createSuggestFormStoreToolbarAction();
    action.resourceFormStore.resourceStore.data = {title: 'Existing title'};
    // $FlowFixMe
    action.resourceFormStore.change = jest.fn();
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Existing title'}
    );
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Suggested title'}
    );
    action.hasSuggestion = true;

    render(action.getNode());
    await user.click(screen.getByText('Insert'));

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
    const user = userEvent.setup();
    const action = createSuggestFormStoreToolbarAction();
    action.resourceFormStore.resourceStore.id = 5;
    action.resourceFormStore.resourceStore.data = {title: 'Existing title'};
    // $FlowFixMe
    action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Existing title'}
    );
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'First suggestion'}
    );

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en');
    Requester.post.mockResolvedValue({title: 'Second suggestion'});

    render(action.getNode());
    await user.click(screen.getByText('Regenerate'));

    await waitFor(() => {
        const suggestionFormStore = action.suggestionFormStore;

        expect(suggestionFormStore && suggestionFormStore.data).toEqual({title: 'Second suggestion'});
    });

    expect(action.showDialog).toBe(true);
});

test('Regenerate blanks the suggestion fields while the new response is in flight', async() => {
    const user = userEvent.setup();
    const action = createSuggestFormStoreToolbarAction();
    action.resourceFormStore.resourceStore.id = 5;
    action.resourceFormStore.resourceStore.data = {title: 'Existing title'};
    // $FlowFixMe
    action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Existing title'}
    );
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'First suggestion'}
    );

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en');
    let resolveRequest: (response: {title: string}) => void = () => {};
    Requester.post.mockReturnValue(new Promise((resolve) => {
        resolveRequest = resolve;
    }));

    render(action.getNode());
    await user.click(screen.getByText('Regenerate'));

    // $FlowFixMe
    expect(action.suggestionFormStore.data).toEqual({title: undefined});
    expect(action.hasSuggestion).toBe(false);
    expect(action.loading).toBe(true);

    resolveRequest({title: 'Second suggestion'});
    await waitFor(() => expect(action.loading).toBe(false));

    // $FlowFixMe
    expect(action.suggestionFormStore.data).toEqual({title: 'Second suggestion'});
    expect(action.hasSuggestion).toBe(true);
    expect(action.loading).toBe(false);
});

test('Temporary error while generating keeps the dialog open with a snackbar', async() => {
    const user = userEvent.setup();
    const action = createSuggestFormStoreToolbarAction();
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Existing title'}
    );
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'First suggestion'}
    );

    const error = new Error('Test Error');
    // $FlowFixMe
    error.json = jest.fn().mockResolvedValue({messageKey: 'sulu_ai.ai_request_failed'});
    Requester.post.mockRejectedValue(error);

    render(action.getNode());
    await user.click(screen.getByText('Regenerate'));

    await waitFor(() => expect(action.dialogSnackbarMessage).toBe('sulu_admin.request_failed'));

    expect(action.showDialog).toBe(true);
    expect(action.dialogSnackbarType).toBe('warning');
    expect(action.form.errors).toHaveLength(0);
    expect(action.form.warnings).toHaveLength(0);
    // generate() blanks the suggestion fields before requesting, so what's on screen behind
    // this warning is empty, not the previous suggestion - Insert must stay disabled over it
    expect(action.hasSuggestion).toBe(false);
});

test('Account limit error while generating closes the dialog with a terminal error', async() => {
    const user = userEvent.setup();
    setAccountLimitContactEmail('admin@example.com');

    const action = createSuggestFormStoreToolbarAction();
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Existing title'}
    );
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'First suggestion'}
    );

    const error = new Error('Test Error');
    // $FlowFixMe
    error.json = jest.fn().mockResolvedValue({messageKey: 'sulu_ai.out_of_credits'});
    Requester.post.mockRejectedValue(error);

    render(action.getNode());
    await user.click(screen.getByText('Regenerate'));

    await waitFor(() => expect(action.form.errors).toHaveLength(1));

    expect(action.showDialog).toBe(false);
    const lastError = action.form.errors[action.form.errors.length - 1];
    expect(lastError).toEqual(expect.objectContaining({
        title: 'sulu_ai.out_of_credits',
        message: 'sulu_ai.out_of_credits_description',
    }));

    setAccountLimitContactEmail(undefined);
});

test('Cancel closes the dialog without clearing its content mid-transition', async() => {
    const user = userEvent.setup();
    const action = createSuggestFormStoreToolbarAction();
    action.showDialog = true;
    const originalFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Existing title'}
    );
    action.originalFormStore = originalFormStore;
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Suggestion'}
    );

    render(action.getNode());
    await user.click(screen.getByText('Cancel'));

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

    const staleOriginalFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Stale'}
    );
    const staleSuggestionFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Stale suggestion'}
    );
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

test('Insert stays disabled until a suggestion has loaded, even once the store exists', async() => {
    const user = userEvent.setup();
    const action = createSuggestFormStoreToolbarAction();
    action.resourceFormStore.resourceStore.id = 5;
    action.resourceFormStore.resourceStore.data = {title: 'Existing title'};
    // $FlowFixMe
    action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Existing title'}
    );
    // the suggestion store exists (as it would right after the dialog opens, or after a
    // temporary error leaves the dialog open) but no response has landed in it yet
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {});

    const {rerender} = render(action.getNode());
    expect(screen.getByRole('button', {name: 'Insert'})).toBeDisabled();

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en');
    Requester.post.mockResolvedValue({title: 'Suggested title'});
    await user.click(screen.getByText('Regenerate'));
    await waitFor(() => expect(action.hasSuggestion).toBe(true));

    rerender(action.getNode());
    expect(screen.getByRole('button', {name: 'Insert'})).toBeEnabled();
});

test('Insert stays disabled while the suggestion store is still loading, even after a response landed', async() => {
    const action = createSuggestFormStoreToolbarAction();
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Existing title'}
    );
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {});
    // SchemaFormStoreDecorator.changeMultiple() defers through its own "when(innerFormStore)"
    // guard, so a response can set hasSuggestion=true before the suggestion store has actually
    // finished loading and applied it - confirmDisabled must keep checking the store itself too
    // $FlowFixMe
    action.suggestionFormStore.loading = true;
    action.hasSuggestion = true;

    const {rerender} = render(action.getNode());
    rerender(action.getNode());

    expect(screen.getByRole('button', {name: 'Insert'})).toBeDisabled();
});

test('Regenerate waits for the optimize form store to finish loading before sending its value', async() => {
    const action = createSuggestFormStoreToolbarAction({formKey: 'test_options_form'});
    action.resourceFormStore.resourceStore.id = 5;
    action.resourceFormStore.resourceStore.data = {title: 'Existing title'};
    // $FlowFixMe
    action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en');
    Requester.post.mockResolvedValue({title: 'Suggested title'});

    // the optimize form's own schema resolves later than the click, reproducing the live race:
    // a real "getSchema" call that has not answered yet when generate() builds the request body
    let resolveSchema: (schema: Object) => void = () => {};
    // $FlowFixMe
    metadataStore.getSchema.mockImplementationOnce(() => new Promise((resolve) => {
        resolveSchema = resolve;
    }));
    // $FlowFixMe
    metadataStore.getJsonSchema.mockImplementationOnce(() => Promise.resolve(undefined));
    // $FlowFixMe
    memoryFormStoreFactory.createFromFormKey.mockImplementationOnce((formKey, data) =>
        new SchemaFormStoreDecorator(
            (schema, jsonSchema) => new MemoryFormStore(data || {}, schema, jsonSchema),
            formKey
        )
    );

    const config = action.getToolbarItemConfig();
    await config.onClick();
    await waitFor(() => {
        const formStore = action.formStore;

        expect(formStore && formStore.loading).toBe(true);
    });

    // formStore's schema has not resolved yet - the request must not have gone out with a
    // premature, still-loading "data" value
    expect(Requester.post).not.toHaveBeenCalled();

    resolveSchema({});
    await waitFor(() => expect(Requester.post).toHaveBeenCalledWith('/test/5?locale=en', {
        content: {title: 'Existing title'},
        data: {optimize: true},
    }));
});

test('Regenerate falls through to a warning if the options form never finishes loading', async() => {
    jest.useFakeTimers();

    // $FlowFixMe
    let defaultCreateFromFormKey;

    try {
        const action = createSuggestFormStoreToolbarAction({formKey: 'test_options_form'});
        action.resourceFormStore.resourceStore.id = 5;
        action.resourceFormStore.resourceStore.data = {title: 'Existing title'};
        // $FlowFixMe
        action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');

        symfonyRouting.generate.mockReturnValue('/test/5?locale=en');

        // the options form's schema request failed without ever settling (SchemaFormStoreDecorator
        // has no error state), so "loading" would otherwise stay true forever. Keyed by formKey
        // rather than call order, since originalFormStore/suggestionFormStore are also created
        // (with the unrelated suggestionFormKey) in between the two "test_options_form" calls.
        const stuckFormStore = {data: {}, loading: true, destroy: jest.fn()};
        const recreatedFormStore = {data: {optimize: true}, loading: false, destroy: jest.fn()};
        let optionsFormStoreCalls = 0;
        defaultCreateFromFormKey = memoryFormStoreFactory.createFromFormKey.getMockImplementation();
        memoryFormStoreFactory.createFromFormKey.mockImplementation((formKey, data) => {
            if (formKey === 'test_options_form') {
                optionsFormStoreCalls += 1;

                return optionsFormStoreCalls === 1 ? stuckFormStore : recreatedFormStore;
            }

            // $FlowFixMe
            return defaultCreateFromFormKey(formKey, data);
        });

        const config = action.getToolbarItemConfig();
        const clickPromise = config.onClick();

        // $FlowFixMe - not in the outdated jest flow-typed stub, but supported since Jest 27
        await jest.advanceTimersByTimeAsync(10000);
        await clickPromise;

        expect(Requester.post).not.toHaveBeenCalled();
        expect(action.loading).toBe(false);
        expect(action.dialogSnackbarType).toBe('warning');
        expect(action.dialogSnackbarMessage).toBe('sulu_admin.request_failed');

        // the stuck store's own schema request never retries on its own - replacing it is what
        // lets the next Regenerate actually ask for the schema again instead of timing out again
        expect(stuckFormStore.destroy).toHaveBeenCalled();
        expect(action.formStore).toBe(recreatedFormStore);
    } finally {
        jest.useRealTimers();
        // clearMocks resets calls/instances between tests but not a persistent mockImplementation
        // $FlowFixMe
        memoryFormStoreFactory.createFromFormKey.mockImplementation(defaultCreateFromFormKey);
    }
});

test('Insert stays disabled if a regenerate fails after a suggestion had already loaded', async() => {
    const user = userEvent.setup();
    const action = createSuggestFormStoreToolbarAction();
    action.resourceFormStore.resourceStore.id = 5;
    action.resourceFormStore.resourceStore.data = {title: 'Existing title'};
    // $FlowFixMe
    action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Existing title'}
    );
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey('test_suggestion_form', {});

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en');
    Requester.post.mockResolvedValueOnce({title: 'First suggestion'});

    const {rerender} = render(action.getNode());
    await user.click(screen.getByText('Regenerate'));
    await waitFor(() => expect(action.hasSuggestion).toBe(true));

    rerender(action.getNode());
    expect(action.hasSuggestion).toBe(true);
    expect(screen.getByRole('button', {name: 'Insert'})).toBeEnabled();

    const error = new Error('Test Error');
    // $FlowFixMe
    error.json = jest.fn().mockResolvedValue({messageKey: 'sulu_ai.ai_request_failed'});
    Requester.post.mockRejectedValueOnce(error);

    await user.click(screen.getByText('Regenerate'));
    await waitFor(() => expect(action.hasSuggestion).toBe(false));

    rerender(action.getNode());
    expect(action.hasSuggestion).toBe(false);
    expect(screen.getByRole('button', {name: 'Insert'})).toBeDisabled();
});

test('Insert only writes properties the suggestion actually answered, leaving the rest untouched', async() => {
    const user = userEvent.setup();
    const action = createSuggestFormStoreToolbarAction({
        contentExpressions: [
            {property: 'title', get: 'title', path: '/title'},
            {property: 'article', get: 'article', path: '/article'},
        ],
    });
    action.resourceFormStore.resourceStore.data = {title: 'Existing title', article: 'Existing article'};
    // $FlowFixMe
    action.resourceFormStore.change = jest.fn();
    action.showDialog = true;
    action.originalFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Existing title', article: 'Existing article'}
    );
    // the endpoint only answered "title" - "article" stays at blankSuggestionFields()'s
    // undefined placeholder, exactly as it would live after an incomplete response
    action.suggestionFormStore = memoryFormStoreFactory.createFromFormKey(
        'test_suggestion_form',
        {title: 'Suggested title', article: undefined}
    );
    action.hasSuggestion = true;

    render(action.getNode());
    await user.click(screen.getByText('Insert'));

    expect(action.resourceFormStore.change).toHaveBeenCalledWith('/title', 'Suggested title');
    // expect.anything() does not match undefined, so a plain not.toHaveBeenCalledWith() check
    // here would pass whether "/article" was skipped entirely or called with undefined - check
    // the actual calls instead
    // $FlowFixMe
    const articleCalls = action.resourceFormStore.change.mock.calls.filter(([path]) => path === '/article');
    expect(articleCalls).toHaveLength(0);
});
