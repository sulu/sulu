// @flow
import React from 'react';
import {
    createDeferred,
    createRoute,
    createRouterMock,
    mockResizeObserver,
} from 'sulu-admin-bundle/utils/TestHelper';
import {render, screen, waitFor, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';

jest.mock('sulu-admin-bundle/containers', () => {
    const React = require('react');

    return {
        SingleListOverlay: jest.fn((props) => {
            if (!props.open) {
                return null;
            }

            function handleConfirm() {
                props.onConfirm({id: 'some-uuid'});
            }

            return (
                <div
                    aria-label={props.title}
                    data-options={JSON.stringify(props.options)}
                    role="dialog"
                >
                    <button onClick={props.onClose} type="button">close-overlay</button>
                    {React.createElement(
                        'button',
                        {onClick: handleConfirm, type: 'button'},
                        'confirm-overlay'
                    )}
                </div>
            );
        }),
        withToolbar: require('sulu-admin-bundle/containers/Toolbar/withToolbar').default,
    };
});
jest.mock('sulu-admin-bundle/utils/Translator');
jest.mock('../stores/SnippetAreaStore', () => jest.fn());

const mockGetCacheClearNode = jest.fn();
const mockGetCacheClearToolbarItemConfig = jest.fn().mockReturnValue({label: 'clear-cache'});

jest.mock('sulu-website-bundle/containers/CacheClearToolbarAction', () => jest.fn(function() {
    this.getNode = mockGetCacheClearNode;
    this.getToolbarItemConfig = mockGetCacheClearToolbarItemConfig;
}));
jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
    this.attributes = {
        webspace: 'sulu',
    };
}));

mockResizeObserver();

beforeEach(() => {
    jest.clearAllMocks();
    jest.resetModules();
});

function setSnippetAreaStore(store: Object) {
    const SnippetAreaStore: any = require('../stores/SnippetAreaStore');

    SnippetAreaStore.mockImplementation(() => store);

    return SnippetAreaStore;
}

function createSnippetRouter(routeOptions: Object = {}) {
    const router = createRouterMock({
        attributes: {
            webspace: 'sulu',
        },
        route: createRoute(routeOptions, {}, [], {
            name: 'snippet_areas',
            path: '/snippet-areas',
            type: 'snippet_areas',
        }),
    });
    router.addUpdateRouteHook.mockReturnValue(jest.fn());

    return router;
}

function renderSnippetAreas(router) {
    const SnippetAreas = require('../SnippetAreas').default;
    const Toolbar = require('sulu-admin-bundle/containers/Toolbar').default;

    render(<Toolbar />);

    return render(<SnippetAreas route={router.route} router={router} />);
}

function getSingleListOverlay() {
    return screen.getByRole('dialog', {name: 'sulu_snippet.selection_overlay_title'});
}

function querySingleListOverlay() {
    return screen.queryByRole('dialog', {name: 'sulu_snippet.selection_overlay_title'});
}

function getDeleteDialog() {
    return screen.getByText('sulu_admin.delete_warning_title').closest('.dialogContainer');
}

function queryDeleteDialog() {
    const title = screen.queryByText('sulu_admin.delete_warning_title');

    return title && title.closest('.dialogContainer');
}

function expectDeleteDialogClosed() {
    const dialog = queryDeleteDialog();

    if (!dialog) {
        expect(dialog).toBeNull();
        return;
    }

    expect(dialog).not.toHaveClass('open');
}

test('Show loader when loading snippet areas', () => {
    const router = createSnippetRouter();

    setSnippetAreaStore({loading: true});

    renderSnippetAreas(router);
    expect(screen.getByText((content, element) => !!element && element.classList.contains('spinner')))
        .toBeInTheDocument();
});

test('Render snippet areas with data as table', () => {
    const router = createSnippetRouter();

    const SnippetAreaStore = setSnippetAreaStore({
        snippetAreas: {
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
        },
    });

    const {container} = renderSnippetAreas(router);
    expect(container).toMatchSnapshot();
    expect(SnippetAreaStore).toHaveBeenCalledWith('sulu');
});

test('Close after clicking add without choosing a snippet', async() => {
    const user = userEvent.setup();

    const router = createSnippetRouter();

    const snippetAreaStore = {
        snippetAreas: {
            default: {
                defaultTitle: null,
                defaultUuid: null,
                key: 'default',
                title: 'Default',
            },
        },
        save: jest.fn(),
    };
    setSnippetAreaStore(snippetAreaStore);

    renderSnippetAreas(router);

    expect(querySingleListOverlay()).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'su-plus-circle'}));

    await waitFor(() => {
        expect(getSingleListOverlay()).toHaveAttribute('data-options', JSON.stringify({areas: 'default'}));
    });

    await user.click(screen.getByRole('button', {name: 'close-overlay'}));

    expect(querySingleListOverlay()).not.toBeInTheDocument();

    expect(snippetAreaStore.save).not.toHaveBeenCalled();
});

test('Save after adding a new snippet area', async() => {
    const user = userEvent.setup();

    const router = createSnippetRouter();

    const saveRequest = createDeferred<void>();
    const snippetAreaStore = {
        snippetAreas: {
            default: {
                defaultTitle: null,
                defaultUuid: null,
                key: 'default',
                title: 'Default',
            },
        },
        save: jest.fn().mockReturnValue(saveRequest.promise),
    };
    setSnippetAreaStore(snippetAreaStore);

    renderSnippetAreas(router);

    expect(querySingleListOverlay()).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'su-plus-circle'}));
    await waitFor(() => expect(getSingleListOverlay()).toBeInTheDocument());

    await user.click(screen.getByRole('button', {name: 'confirm-overlay'}));
    expect(getSingleListOverlay()).toBeInTheDocument();

    expect(snippetAreaStore.save).toHaveBeenCalledWith('default', 'some-uuid');

    saveRequest.resolve();

    await waitFor(() => expect(querySingleListOverlay()).not.toBeInTheDocument());
});

test('Close after clicking delete and cancel dialog', async() => {
    const user = userEvent.setup();

    const router = createSnippetRouter();

    const snippetAreaStore = {
        snippetAreas: {
            default: {
                defaultTitle: 'Default Snippet',
                defaultUuid: 'some-uuid',
                key: 1,
                title: 'Default',
            },
        },
        delete: jest.fn(),
    };
    setSnippetAreaStore(snippetAreaStore);

    renderSnippetAreas(router);

    expectDeleteDialogClosed();

    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));
    expect(getDeleteDialog()).toHaveClass('open');

    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));
    expectDeleteDialogClosed();

    expect(snippetAreaStore.delete).not.toHaveBeenCalled();
});

test('Delete after confirming the confirmation dialog', async() => {
    const user = userEvent.setup();

    const router = createSnippetRouter();

    const deleteRequest = createDeferred<void>();
    const snippetAreaStore = {
        snippetAreas: {
            default: {
                defaultTitle: 'Default Snippet',
                defaultUuid: 'some-uuid',
                key: 'default',
                title: 'Default',
            },
        },
        delete: jest.fn().mockReturnValue(deleteRequest.promise),
    };
    setSnippetAreaStore(snippetAreaStore);

    renderSnippetAreas(router);

    expectDeleteDialogClosed();

    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));
    expect(getDeleteDialog()).toHaveClass('open');

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));
    expect(getDeleteDialog()).toHaveClass('open');

    expect(snippetAreaStore.delete).toHaveBeenCalledWith('default');

    deleteRequest.resolve();

    await waitFor(() => expectDeleteDialogClosed());
});

test('Navigate when selected default snippet is clicked', async() => {
    const user = userEvent.setup();

    const router = createSnippetRouter({
        snippetEditView: 'sulu_snippet.edit_form',
    });
    setSnippetAreaStore({
        snippetAreas: {
            default: {
                defaultTitle: 'Default Snippet',
                defaultUuid: 'some-uuid',
                key: 1,
                title: 'Default',
            },
        },
        save: jest.fn(),
    });

    renderSnippetAreas(router);
    await user.click(screen.getByRole('button', {name: 'Default Snippet'}));

    expect(router.navigate).toHaveBeenCalledWith('sulu_snippet.edit_form', {id: 'some-uuid'});
});

test('Should use CacheClearToolbarAction for cache clearing', () => {
    const router = createSnippetRouter();

    setSnippetAreaStore({
        snippetAreas: {
            default: {
                defaultTitle: 'Default Snippet',
                defaultUuid: 'some-uuid',
                key: 'default',
                title: 'Default',
            },
        },
    });

    renderSnippetAreas(router);

    expect(mockGetCacheClearNode).toHaveBeenCalledWith();
    expect(mockGetCacheClearToolbarItemConfig).toHaveBeenCalledWith();
    expect(screen.getByRole('button', {name: 'clear-cache'})).toBeInTheDocument();
});

test('Show forbidden hint when user has no permission', () => {
    const router = createSnippetRouter();

    setSnippetAreaStore({
        forbidden: true,
        loading: false,
        snippetAreas: {},
        unexpectedError: false,
    });

    renderSnippetAreas(router);

    expect(screen.getByText('sulu_admin.no_permissions')).toBeInTheDocument();
    expect(screen.getByLabelText('su-lock')).toBeInTheDocument();
});

test('Show error hint when unexpected error occurs', () => {
    const router = createSnippetRouter();

    setSnippetAreaStore({
        forbidden: false,
        loading: false,
        snippetAreas: {},
        unexpectedError: true,
    });

    renderSnippetAreas(router);

    const hint = screen.getByText('sulu_admin.unexpected_error').closest('.hint');
    if (!hint) {
        throw new Error('Expected error hint to be rendered');
    }

    expect(within(hint).getByLabelText('su-exclamation-triangle')).toBeInTheDocument();
});
