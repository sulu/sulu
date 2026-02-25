// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {Dialog, Loader} from 'sulu-admin-bundle/components';
import {SingleListOverlay, withToolbar} from 'sulu-admin-bundle/containers';
import {Route, Router} from 'sulu-admin-bundle/services';
import {findWithHighOrderFunction, getLatestMockProps} from 'sulu-admin-bundle/utils/TestHelper';
import {CacheClearToolbarAction} from 'sulu-website-bundle/containers';
import SnippetAreas from '../SnippetAreas';
import SnippetAreaStore from '../stores/SnippetAreaStore';

const toolbarFunction = findWithHighOrderFunction(withToolbar, SnippetAreas);
const SnippetAreaStoreMock = (SnippetAreaStore: any);

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');
    const actual = jest.requireActual('sulu-admin-bundle/components');

    return {
        ...actual,
        Dialog: jest.fn(() => null),
        Loader: jest.fn(() => <div data-testid="loader" />),
    };
});

jest.mock('sulu-admin-bundle/containers', () => ({
    SingleListOverlay: jest.fn(() => null),
    withToolbar: jest.fn((Component) => Component),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

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

const getLatestSingleListOverlayProps = () => getLatestMockProps((SingleListOverlay: any));
const getLatestDialogProps = () => getLatestMockProps((Dialog: any));

beforeEach(() => {
    jest.clearAllMocks();
});

test('Show loader when loading snippet areas', () => {
    SnippetAreaStoreMock.mockImplementation(function() {
        this.loading = true;
    });

    const router = new Router();

    render(<SnippetAreas route={router.route} router={router} />);

    expect(Loader).toHaveBeenCalled();
    expect(screen.getByTestId('loader')).toBeInTheDocument();
});

test('Render snippet areas with data as table', () => {
    SnippetAreaStoreMock.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: null,
                snippetUuid: null,
                key: 'default',
                title: 'Default',
            },
            footer: {
                snippetTitle: 'Footer Snippet',
                snippetUuid: 'some-other-uuid',
                key: 'footer',
                title: 'Footer',
            },
        };
    });

    const router = new Router();
    const {asFragment} = render(<SnippetAreas route={router.route} router={router} />);

    expect(asFragment()).toMatchSnapshot();
    expect(SnippetAreaStoreMock).toHaveBeenCalledWith('sulu');
});

test('Close after clicking add without choosing a snippet', async() => {
    SnippetAreaStoreMock.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: null,
                snippetUuid: null,
                key: 'default',
                title: 'Default',
            },
        };

        this.save = jest.fn();
    });

    const router = new Router();

    render(<SnippetAreas route={router.route} router={router} />);

    const snippetAreaStore = SnippetAreaStoreMock.mock.instances[0];

    expect(getLatestSingleListOverlayProps().open).toEqual(false);

    await userEvent.click(screen.getByRole('button', {name: 'su-plus-circle'}));

    expect(getLatestSingleListOverlayProps().open).toEqual(true);
    expect(getLatestSingleListOverlayProps().options).toEqual({areas: 'default'});

    act(() => {
        getLatestSingleListOverlayProps().onClose();
    });

    expect(getLatestSingleListOverlayProps().open).toEqual(false);
    expect(snippetAreaStore.save).not.toHaveBeenCalled();
});

test('Save after adding a new snippet area', async() => {
    const savePromise = Promise.resolve();

    SnippetAreaStoreMock.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: null,
                snippetUuid: null,
                key: 'default',
                title: 'Default',
            },
        };

        this.save = jest.fn().mockReturnValue(savePromise);
    });

    const router = new Router();

    render(<SnippetAreas route={router.route} router={router} />);

    const snippetAreaStore = SnippetAreaStoreMock.mock.instances[0];

    expect(getLatestSingleListOverlayProps().open).toEqual(false);

    await userEvent.click(screen.getByRole('button', {name: 'su-plus-circle'}));

    expect(getLatestSingleListOverlayProps().open).toEqual(true);

    act(() => {
        getLatestSingleListOverlayProps().onConfirm({id: 'some-uuid'});
    });

    expect(getLatestSingleListOverlayProps().open).toEqual(true);
    expect(snippetAreaStore.save).toHaveBeenCalledWith('default', 'some-uuid');

    await act(async() => {
        await savePromise;
    });

    expect(getLatestSingleListOverlayProps().open).toEqual(false);
});

test('Close after clicking delete and cancel dialog', async() => {
    SnippetAreaStoreMock.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: 'Default Snippet',
                snippetUuid: 'some-uuid',
                key: 1,
                title: 'Default',
            },
        };

        this.save = jest.fn();
    });

    const router = new Router();

    render(<SnippetAreas route={router.route} router={router} />);

    const snippetAreaStore = SnippetAreaStoreMock.mock.instances[0];

    expect(getLatestDialogProps().open).toEqual(false);

    await userEvent.click(screen.getByRole('button', {name: 'su-trash-alt'}));

    expect(getLatestDialogProps().open).toEqual(true);

    act(() => {
        getLatestDialogProps().onCancel();
    });

    expect(getLatestDialogProps().open).toEqual(false);
    expect(snippetAreaStore.save).not.toHaveBeenCalled();
});

test('Delete after confirming the confirmation dialog', async() => {
    const deletePromise = Promise.resolve();

    SnippetAreaStoreMock.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: 'Default Snippet',
                snippetUuid: 'some-uuid',
                key: 'default',
                title: 'Default',
            },
        };

        this.delete = jest.fn().mockReturnValue(deletePromise);
    });

    const router = new Router();

    render(<SnippetAreas route={router.route} router={router} />);

    const snippetAreaStore = SnippetAreaStoreMock.mock.instances[0];

    expect(getLatestDialogProps().open).toEqual(false);

    await userEvent.click(screen.getByRole('button', {name: 'su-trash-alt'}));

    expect(getLatestDialogProps().open).toEqual(true);

    act(() => {
        getLatestDialogProps().onConfirm();
    });

    expect(getLatestDialogProps().open).toEqual(true);
    expect(snippetAreaStore.delete).toHaveBeenCalledWith('default');

    await act(async() => {
        await deletePromise;
    });

    expect(getLatestDialogProps().open).toEqual(false);
});

test('Navigate when selected default snippet is clicked', async() => {
    SnippetAreaStoreMock.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: 'Default Snippet',
                snippetUuid: 'some-uuid',
                key: 1,
                title: 'Default',
            },
        };

        this.save = jest.fn();
    });

    const route = new Route({
        name: 'snippet_areas',
        path: '/snippet-areas',
        type: 'snippet_areas',
        options: {
            snippetEditView: 'sulu_snippet.edit_form',
        },
    });

    const router = new Router();

    render(<SnippetAreas route={route} router={router} />);

    await userEvent.click(screen.getByRole('button', {name: 'Default Snippet'}));

    expect(router.navigate).toHaveBeenCalledWith('sulu_snippet.edit_form', {id: 'some-uuid'});
});

test('Should use CacheClearToolbarAction for cache clearing', () => {
    SnippetAreaStoreMock.mockImplementation(function() {
        this.snippetAreas = {
            default: {
                snippetTitle: 'Default Snippet',
                snippetUuid: 'some-uuid',
                key: 'default',
                title: 'Default',
            },
        };
    });

    const router = new Router();

    render(<SnippetAreas route={router.route} router={router} />);

    const cacheClearToolbarAction = (CacheClearToolbarAction: any).mock.instances[0];

    expect(cacheClearToolbarAction.getNode).toHaveBeenCalled();

    expect(cacheClearToolbarAction.getToolbarItemConfig).not.toHaveBeenCalled();

    toolbarFunction.call({cacheClearToolbarAction});

    expect(cacheClearToolbarAction.getToolbarItemConfig).toHaveBeenCalled();
});

test('Show forbidden hint when user has no permission', () => {
    SnippetAreaStoreMock.mockImplementation(function() {
        this.loading = false;
        this.forbidden = true;
        this.unexpectedError = false;
        this.snippetAreas = {};
    });

    const router = new Router();

    render(<SnippetAreas route={router.route} router={router} />);

    expect(screen.getByLabelText('su-lock')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.no_permissions')).toBeInTheDocument();
});

test('Show error hint when unexpected error occurs', () => {
    SnippetAreaStoreMock.mockImplementation(function() {
        this.loading = false;
        this.forbidden = false;
        this.unexpectedError = true;
        this.snippetAreas = {};
    });

    const router = new Router();

    render(<SnippetAreas route={router.route} router={router} />);

    expect(screen.getByLabelText('su-exclamation-triangle')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.unexpected_error')).toBeInTheDocument();
});
