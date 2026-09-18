// @flow
import {observable} from 'mobx';
import ListStore from '../../../../containers/List/stores/ListStore';
import ResourceRequester from '../../../../services/ResourceRequester';
import Router from '../../../../services/Router';
import ResourceStore from '../../../../stores/ResourceStore';
import List from '../../../../views/List';
import PublishingToolbarAction from '../../toolbarActions/PublishingToolbarAction';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../services/ResourceRequester', () => ({
    post: jest.fn(),
}));

jest.mock('../../../../containers/List/stores/ListStore', () => jest.fn(function(resourceKey) {
    this.resourceKey = resourceKey;
    this.queryOptions = {locale: 'en'};
    this.selections = [];
    Object.defineProperty(this, 'selectionIds', {get: () => this.selections.map((item) => item.id)});
    this.deselectById = jest.fn();
    this.reload = jest.fn();
}));

jest.mock('../../../../views/List/List', () => jest.fn(function() {
    this.errors = [];
}));

jest.mock('../../../../services/Router/Router', () => jest.fn());

function createPublishingToolbarAction(options = {}) {
    const router = new Router({});
    const listStore = new ListStore('snippets', 'snippets', 'test', {page: observable.box(1)});
    const list = new List({
        route: router.route,
        router,
    });
    const locales = [];
    const resourceStore = new ResourceStore('test');

    return new PublishingToolbarAction(listStore, list, router, locales, resourceStore, options);
}

test('Return disabled config without selection', () => {
    const publishingToolbarAction = createPublishingToolbarAction();

    expect(publishingToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
        icon: 'su-publish',
        label: 'sulu_admin.publishing',
        loading: false,
        type: 'dropdown',
        options: [
            expect.objectContaining({disabled: true, label: 'sulu_admin.publish'}),
            expect.objectContaining({disabled: true, label: 'sulu_admin.unpublish'}),
        ],
    }));
});

test('Disable unpublish if no selected item is published', () => {
    const publishingToolbarAction = createPublishingToolbarAction();
    publishingToolbarAction.listStore.selections.push({id: 1, published: null});

    const {disabled, options} = (publishingToolbarAction.getToolbarItemConfig(): Object);

    expect(disabled).toEqual(false);
    expect(options[0].disabled).toEqual(false);
    expect(options[1].disabled).toEqual(true);
});

test('Disable only publish if all selected items are published without a draft', () => {
    const publishingToolbarAction = createPublishingToolbarAction();
    publishingToolbarAction.listStore.selections.push({id: 1, published: '2026-01-01', publishedState: true});

    const {disabled, options} = (publishingToolbarAction.getToolbarItemConfig(): Object);

    expect(disabled).toEqual(false);
    expect(options[0].disabled).toEqual(true);
    expect(options[1].disabled).toEqual(false);
});

test('Disable publish and unpublish if all selected items are ghosts', () => {
    const publishingToolbarAction = createPublishingToolbarAction();
    publishingToolbarAction.listStore.selections.push({id: 1, ghostLocale: 'de', published: '2026-01-01'});

    const {disabled, options} = (publishingToolbarAction.getToolbarItemConfig(): Object);

    expect(disabled).toEqual(true);
    expect(options[0].disabled).toEqual(true);
    expect(options[1].disabled).toEqual(true);
});

test('Publish every selected item with a draft and deselect the whole selection', () => {
    ResourceRequester.post.mockReturnValue(Promise.resolve({}));

    const publishingToolbarAction = createPublishingToolbarAction();
    const {listStore} = publishingToolbarAction;
    listStore.selections.push(
        {id: 1},
        {id: 2, ghostLocale: 'de'},
        {id: 3, published: '2026-01-01', publishedState: false},
        {id: 4, published: '2026-01-01', publishedState: true}
    );

    const {options} = (publishingToolbarAction.getToolbarItemConfig(): Object);
    options[0].onClick();

    expect(publishingToolbarAction.loading).toEqual(true);
    expect(ResourceRequester.post).toHaveBeenCalledTimes(2);
    expect(ResourceRequester.post)
        .toHaveBeenCalledWith('snippets', undefined, {action: 'publish', id: 1, locale: 'en'});
    expect(ResourceRequester.post)
        .toHaveBeenCalledWith('snippets', undefined, {action: 'publish', id: 3, locale: 'en'});

    return new Promise((resolve) => setTimeout(resolve)).then(() => {
        expect(publishingToolbarAction.loading).toEqual(false);
        expect(listStore.deselectById).toHaveBeenCalledTimes(4);
        expect(listStore.deselectById).toHaveBeenCalledWith(1);
        expect(listStore.deselectById).toHaveBeenCalledWith(2);
        expect(listStore.deselectById).toHaveBeenCalledWith(3);
        expect(listStore.deselectById).toHaveBeenCalledWith(4);
        expect(listStore.reload).toHaveBeenCalledWith();
    });
});

test('Unpublish only the published items after the dialog is confirmed', () => {
    ResourceRequester.post.mockReturnValue(Promise.resolve({}));

    const publishingToolbarAction = createPublishingToolbarAction();
    publishingToolbarAction.listStore.selections.push({id: 1}, {id: 2, published: '2026-01-01'});

    const {options} = (publishingToolbarAction.getToolbarItemConfig(): Object);
    options[1].onClick();

    expect(publishingToolbarAction.showUnpublishDialog).toEqual(true);
    expect(ResourceRequester.post).not.toHaveBeenCalled();

    publishingToolbarAction.handleUnpublishConfirm();

    expect(ResourceRequester.post).toHaveBeenCalledTimes(1);
    expect(ResourceRequester.post)
        .toHaveBeenCalledWith('snippets', undefined, {action: 'unpublish', id: 2, locale: 'en'});
});

test('Close the dialog without a request when it is cancelled', () => {
    const publishingToolbarAction = createPublishingToolbarAction();
    publishingToolbarAction.listStore.selections.push({id: 1, published: '2026-01-01'});

    const {options} = (publishingToolbarAction.getToolbarItemConfig(): Object);
    options[1].onClick();
    publishingToolbarAction.handleUnpublishCancel();

    expect(publishingToolbarAction.showUnpublishDialog).toEqual(false);
    expect(ResourceRequester.post).not.toHaveBeenCalled();
});

test('Push the error of every failed request to the list and keep its item selected', () => {
    ResourceRequester.post
        .mockReturnValueOnce(Promise.reject({json: () => Promise.resolve({detail: 'Transition not available'})}))
        .mockReturnValueOnce(Promise.resolve({}))
        .mockReturnValueOnce(Promise.reject({json: () => Promise.reject(new Error('no json'))}));

    const publishingToolbarAction = createPublishingToolbarAction();
    const {listStore} = publishingToolbarAction;
    const items = [{id: 1}, {id: 2}, {id: 3}];
    listStore.selections.push(...items, {id: 4, ghostLocale: 'de'});

    return publishingToolbarAction.applyTransition('publish', items).then(() => {
        expect(publishingToolbarAction.list.errors)
            .toEqual(['Transition not available', 'sulu_admin.unexpected_error']);
        expect(listStore.deselectById).toHaveBeenCalledTimes(2);
        expect(listStore.deselectById).toHaveBeenCalledWith(2);
        expect(listStore.deselectById).toHaveBeenCalledWith(4);
        expect(listStore.reload).toHaveBeenCalledWith();
    });
});
