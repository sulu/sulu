// @flow
import React from 'react';
import {
    createRoute,
    createRouterMock,
    findAllElementsByType,
    findElementByType,
    findWithHighOrderFunction,
    renderWithRef,
    waitForReaction,
} from 'sulu-admin-bundle/utils/TestHelper';

jest.mock('sulu-admin-bundle/containers', () => ({
    SingleListOverlay: jest.fn(() => null),
    withToolbar: jest.fn((Component) => Component),
}));
jest.mock('sulu-admin-bundle/utils/Translator');
jest.mock('../stores/SnippetAreaStore', () => jest.fn());

jest.mock('sulu-website-bundle/containers/CacheClearToolbarAction', () => jest.fn(function() {
    this.getNode = jest.fn();
    this.getToolbarItemConfig = jest.fn();
}));
jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
    this.attributes = {
        webspace: 'sulu',
    };
}));

beforeEach(() => {
    jest.resetModules();
});

function createSnippetRouter(routeOptions: Object = {}) {
    return createRouterMock({
        attributes: {
            webspace: 'sulu',
        },
        route: createRoute(routeOptions, {}, [], {
            name: 'snippet_areas',
            path: '/snippet-areas',
            type: 'snippet_areas',
        }),
    });
}

function getButton(snippetAreas, className) {
    const button = findAllElementsByType(snippetAreas.render(), 'Button')
        .find((button) => button.props.className === className);

    if (!button) {
        throw new Error('Button not found!');
    }

    return button;
}

test('Show loader when loading snippet areas', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');

    const router = createSnippetRouter();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.loading = true;
    });

    const {instance: snippetAreas} = renderWithRef(<SnippetAreas route={router.route} router={router} />);
    expect(findElementByType(snippetAreas.render(), 'Loader')).toBeTruthy();
});

test('Render snippet areas with data as table', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');

    const router = createSnippetRouter();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                defaultTitle: null,
                defaultUuid: null,
                key: 'default',
                title: 'Default',
            },
            footer: {
                defaultTitle: 'Footer Snippet',
                defaultUuid: 'some-other-uuid',
                key: 'footer',
                title: 'Footer',
            },
        };
    });

    const {container} = renderWithRef(<SnippetAreas route={router.route} router={router} />);
    expect(container).toMatchSnapshot();
    expect(SnippetAreaStore).toHaveBeenCalledWith('sulu');
});

test('Close after clicking add without choosing a snippet', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;

    const router = createSnippetRouter();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                defaultTitle: null,
                defaultUuid: null,
                key: 'default',
                title: 'Default',
            },
        };

        this.save = jest.fn();
    });

    const {instance: snippetAreas} = renderWithRef(<SnippetAreas route={router.route} router={router} />);
    // $FlowFixMe
    const snippetAreaStore = SnippetAreaStore.mock.instances[0];

    expect(findElementByType(snippetAreas.render(), SingleListOverlay).props.open).toEqual(false);
    const addButton = getButton(snippetAreas, 'addButton');
    addButton.props.onClick(addButton.props.value);
    expect(findElementByType(snippetAreas.render(), SingleListOverlay).props.open).toEqual(true);
    expect(findElementByType(snippetAreas.render(), SingleListOverlay).props.options).toEqual({areas: 'default'});

    findElementByType(snippetAreas.render(), SingleListOverlay).props.onClose();
    expect(findElementByType(snippetAreas.render(), SingleListOverlay).props.open).toEqual(false);

    expect(snippetAreaStore.save).not.toHaveBeenCalled();
});

test('Save after adding a new snippet area', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');
    const SingleListOverlay = require('sulu-admin-bundle/containers').SingleListOverlay;

    const router = createSnippetRouter();

    const savePromise = Promise.resolve();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                defaultTitle: null,
                defaultUuid: null,
                key: 'default',
                title: 'Default',
            },
        };

        this.save = jest.fn().mockReturnValue(savePromise);
    });

    const {instance: snippetAreas} = renderWithRef(<SnippetAreas route={router.route} router={router} />);
    // $FlowFixMe
    const snippetAreaStore = SnippetAreaStore.mock.instances[0];

    expect(findElementByType(snippetAreas.render(), SingleListOverlay).props.open).toEqual(false);
    const addButton = getButton(snippetAreas, 'addButton');
    addButton.props.onClick(addButton.props.value);
    expect(findElementByType(snippetAreas.render(), SingleListOverlay).props.open).toEqual(true);
    findElementByType(snippetAreas.render(), SingleListOverlay).props.onConfirm({id: 'some-uuid'});
    expect(findElementByType(snippetAreas.render(), SingleListOverlay).props.open).toEqual(true);

    expect(snippetAreaStore.save).toHaveBeenCalledWith('default', 'some-uuid');

    return savePromise.then(() => {
        return waitForReaction().then(() => {
            expect(findElementByType(snippetAreas.render(), SingleListOverlay).props.open).toEqual(false);
        });
    });
});

test('Close after clicking delete and cancel dialog', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');

    const router = createSnippetRouter();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                defaultTitle: 'Default Snippet',
                defaultUuid: 'some-uuid',
                key: 1,
                title: 'Default',
            },
        };

        this.save = jest.fn();
    });

    const {instance: snippetAreas} = renderWithRef(<SnippetAreas route={router.route} router={router} />);
    // $FlowFixMe
    const snippetAreaStore = SnippetAreaStore.mock.instances[0];

    expect(findElementByType(snippetAreas.render(), 'Dialog').props.open).toEqual(false);
    const deleteButton = getButton(snippetAreas, 'deleteButton');
    deleteButton.props.onClick(deleteButton.props.value);
    expect(findElementByType(snippetAreas.render(), 'Dialog').props.open).toEqual(true);

    findElementByType(snippetAreas.render(), 'Dialog').props.onCancel();
    expect(findElementByType(snippetAreas.render(), 'Dialog').props.open).toEqual(false);

    expect(snippetAreaStore.save).not.toHaveBeenCalled();
});

test('Delete after confirming the confirmation dialog', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');

    const router = createSnippetRouter();

    const deletePromise = Promise.resolve();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                defaultTitle: 'Default Snippet',
                defaultUuid: 'some-uuid',
                key: 'default',
                title: 'Default',
            },
        };

        this.delete = jest.fn().mockReturnValue(deletePromise);
    });

    const {instance: snippetAreas} = renderWithRef(<SnippetAreas route={router.route} router={router} />);
    // $FlowFixMe
    const snippetAreaStore = SnippetAreaStore.mock.instances[0];

    expect(findElementByType(snippetAreas.render(), 'Dialog').props.open).toEqual(false);
    const deleteButton = getButton(snippetAreas, 'deleteButton');
    deleteButton.props.onClick(deleteButton.props.value);
    expect(findElementByType(snippetAreas.render(), 'Dialog').props.open).toEqual(true);
    findElementByType(snippetAreas.render(), 'Dialog').props.onConfirm();
    expect(findElementByType(snippetAreas.render(), 'Dialog').props.open).toEqual(true);

    expect(snippetAreaStore.delete).toHaveBeenCalledWith('default');

    return deletePromise.then(() => {
        return waitForReaction().then(() => {
            expect(findElementByType(snippetAreas.render(), 'Dialog').props.open).toEqual(false);
        });
    });
});

test('Navigate when selected default snippet is clicked', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');

    const router = createSnippetRouter({
        snippetEditView: 'sulu_snippet.edit_form',
    });
    const {route} = router;

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                defaultTitle: 'Default Snippet',
                defaultUuid: 'some-uuid',
                key: 1,
                title: 'Default',
            },
        };

        this.save = jest.fn();
    });

    const {instance: snippetAreas} = renderWithRef(<SnippetAreas route={route} router={router} />);
    const titleButton = getButton(snippetAreas, 'titleButton');
    titleButton.props.onClick(titleButton.props.value);

    expect(router.navigate).toHaveBeenCalledWith('sulu_snippet.edit_form', {id: 'some-uuid'});
});

test('Should use CacheClearToolbarAction for cache clearing', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');
    const toolbarFunction = findWithHighOrderFunction(withToolbar, SnippetAreas);
    const CacheClearToolbarAction = require('sulu-website-bundle/containers').CacheClearToolbarAction;

    const router = createSnippetRouter();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                defaultTitle: 'Default Snippet',
                defaultUuid: 'some-uuid',
                key: 'default',
                title: 'Default',
            },
        };
    });

    const {instance: snippetAreas} = renderWithRef(
        <SnippetAreas route={router.route} router={router} />
    );

    const cacheClearToolbarAction: CacheClearToolbarAction = (CacheClearToolbarAction: any).mock.instances[0];

    expect(cacheClearToolbarAction.getNode).toHaveBeenCalledWith();

    expect(cacheClearToolbarAction.getToolbarItemConfig).not.toHaveBeenCalled();
    toolbarFunction.call(snippetAreas);
    expect(cacheClearToolbarAction.getToolbarItemConfig).toHaveBeenCalled();
});

test('Show forbidden hint when user has no permission', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');

    const router = createSnippetRouter();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.loading = false;
        this.forbidden = true;
        this.unexpectedError = false;
        this.snippetAreas = {};
    });

    const {instance: snippetAreas} = renderWithRef(<SnippetAreas route={router.route} router={router} />);
    const hint = findElementByType(snippetAreas.render(), 'Hint');

    expect(hint.props.icon).toEqual('su-lock');
    expect(hint.props.title).toEqual('sulu_admin.no_permissions');
});

test('Show error hint when unexpected error occurs', () => {
    const SnippetAreas = require('../SnippetAreas').default;
    const SnippetAreaStore = require('../stores/SnippetAreaStore');

    const router = createSnippetRouter();

    // $FlowFixMe
    SnippetAreaStore.mockImplementation(function() {
        this.loading = false;
        this.forbidden = false;
        this.unexpectedError = true;
        this.snippetAreas = {};
    });

    const {instance: snippetAreas} = renderWithRef(<SnippetAreas route={router.route} router={router} />);
    const hint = findElementByType(snippetAreas.render(), 'Hint');

    expect(hint.props.icon).toEqual('su-exclamation-triangle');
    expect(hint.props.title).toEqual('sulu_admin.unexpected_error');
});
