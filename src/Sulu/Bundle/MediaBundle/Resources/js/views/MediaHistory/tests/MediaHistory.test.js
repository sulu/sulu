/* eslint-disable flowtype/require-valid-file-annotation, react/jsx-no-bind */
import React from 'react';
import {render, screen, waitFor, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import {withToolbar} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {findWithHighOrderFunction} from 'sulu-admin-bundle/utils/TestHelper';
import MediaHistory from '../MediaHistory';

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');
    const actual = jest.requireActual('sulu-admin-bundle/components');

    const Dialog = jest.fn(function DialogMock({cancelText, children, confirmText, onCancel, onConfirm, open, title}) {
        if (!open) {
            return null;
        }

        return (
            <div data-testid="dialog">
                <h2>{title}</h2>
                {children}
                <button onClick={onConfirm} type="button">{confirmText}</button>
                <button onClick={onCancel} type="button">{cancelText}</button>
            </div>
        );
    });

    const Table = jest.fn(function TableMock({children}) {
        return <table data-testid="media-history-table">{children}</table>;
    });

    Table.Header = jest.fn(function HeaderMock({buttons = [], children}) {
        return (
            <thead>
                <tr>
                    {buttons.map((button, index) => <th key={index}>{button.icon}</th>)}
                    {children}
                </tr>
            </thead>
        );
    });

    Table.HeaderCell = jest.fn(function HeaderCellMock({children}) {
        return <th>{children}</th>;
    });

    Table.Body = jest.fn(function BodyMock({children}) {
        return <tbody>{children}</tbody>;
    });

    Table.Row = jest.fn(function RowMock({buttons = [], children, id}) {
        return (
            <tr data-testid={'row-' + id}>
                {buttons.map((button, index) => {
                    function handleClick() {
                        if (button.onClick) {
                            button.onClick(id);
                        }
                    }

                    return (
                        <td key={index}>
                            <button disabled={button.disabled} onClick={handleClick} type="button">
                                {button.icon}
                            </button>
                        </td>
                    );
                })}
                {children}
            </tr>
        );
    });

    Table.Cell = jest.fn(function CellMock({children}) {
        return <td>{children}</td>;
    });

    return {
        ...actual,
        Dialog,
        Table,
    };
});

jest.mock('sulu-admin-bundle/containers', () => ({
    withToolbar: jest.fn((Component) => Component),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id, observableOptions = {}) {
        this.id = id;
        this.locale = observableOptions.locale;
        this.loading = false;
        this.data = {
            versions: {},
        };
        this.reload = jest.fn();
    }),
}));

jest.mock('sulu-admin-bundle/services', () => ({
    ResourceRequester: {
        delete: jest.fn(),
    },
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaHistory);

function createRouter(locales = [], routeName = 'sulu_media.media_history') {
    return {
        attributes: {},
        bind: jest.fn(),
        navigate: jest.fn(),
        restore: jest.fn(),
        route: {
            name: routeName,
            options: {
                locales,
            },
        },
    };
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render a loading MediaHistory view', () => {
    const router = createRouter([]);
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.loading = true;

    const {asFragment} = render(
        <MediaHistory resourceStore={resourceStore} router={router} title="Test 1" />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaHistory view', () => {
    const router = createRouter([]);
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.data.versions = {
        1: {
            created: '2018-10-23T10:18',
            version: 1,
        },
        2: {
            created: '2018-10-23T10:25',
            version: 2,
        },
    };

    const {asFragment} = render(
        <MediaHistory resourceStore={resourceStore} router={router} title="Test 2" />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Open the old media when icon is clicked', async() => {
    window.open = jest.fn();

    const router = createRouter([]);
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.data.versions = {
        1: {
            created: '2018-10-23T10:18',
            url: '/media/1?v=1',
            version: 1,
        },
        2: {
            created: '2018-10-23T10:25',
            url: '/media/1?v=2',
            version: 2,
        },
    };

    const user = userEvent.setup();

    render(<MediaHistory resourceStore={resourceStore} router={router} />);

    await user.click(within(screen.getByTestId('row-2')).getByRole('button', {name: 'su-eye'}));
    expect(window.open).toHaveBeenLastCalledWith('/media/1?v=2&inline=1');

    await user.click(within(screen.getByTestId('row-1')).getByRole('button', {name: 'su-eye'}));
    expect(window.open).toHaveBeenLastCalledWith('/media/1?v=1&inline=1');
});

test('Deleting version should not happen when cancelled', async() => {
    const router = createRouter([]);
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.data.version = 2;
    resourceStore.data.versions = {
        1: {
            created: '2018-10-23T10:18',
            url: '/media/1?v=1',
            version: 1,
        },
        2: {
            created: '2018-10-23T10:25',
            url: '/media/1?v=2',
            version: 2,
        },
    };

    const user = userEvent.setup();

    render(<MediaHistory resourceStore={resourceStore} router={router} />);

    await user.click(within(screen.getByTestId('row-1')).getByRole('button', {name: 'su-trash-alt'}));

    expect(screen.getByText('sulu_admin.delete_warning_title')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));

    expect(screen.queryByText('sulu_admin.delete_warning_title')).not.toBeInTheDocument();
});

test('Deleting version should happen when confirmed', async() => {
    const deletePromise = Promise.resolve({});
    ResourceRequester.delete.mockReturnValue(deletePromise);

    const locale = observable.box('de');

    const router = createRouter([]);
    const resourceStore = new ResourceStore('media', 1, {locale});
    resourceStore.data.version = 2;
    resourceStore.data.versions = {
        1: {
            created: '2018-10-23T10:18',
            url: '/media/1?v=1',
            version: 1,
        },
        2: {
            created: '2018-10-23T10:25',
            url: '/media/1?v=2',
            version: 2,
        },
    };

    const user = userEvent.setup();

    render(<MediaHistory resourceStore={resourceStore} router={router} />);

    await user.click(within(screen.getByTestId('row-1')).getByRole('button', {name: 'su-trash-alt'}));

    expect(screen.getByText('sulu_admin.delete_warning_title')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(ResourceRequester.delete).toBeCalledWith('media_versions', {id: 1, locale, version: 1});

    await waitFor(() => expect(resourceStore.reload).toBeCalledWith());
    expect(screen.queryByText('sulu_admin.delete_warning_title')).not.toBeInTheDocument();
});

test('Deleting version should be disabled on latest version', () => {
    const locale = observable.box('de');

    const router = createRouter([]);
    const resourceStore = new ResourceStore('media', 1, {locale});
    resourceStore.data.version = 2;
    resourceStore.data.versions = {
        1: {
            created: '2018-10-23T10:18',
            url: '/media/1?v=1',
            version: 1,
        },
        2: {
            created: '2018-10-23T10:25',
            url: '/media/1?v=2',
            version: 2,
        },
    };

    render(<MediaHistory resourceStore={resourceStore} router={router} />);

    expect(within(screen.getByTestId('row-2')).getByRole('button', {name: 'su-lock'})).toBeDisabled();
    expect(within(screen.getByTestId('row-1')).getByRole('button', {name: 'su-trash-alt'})).toBeEnabled();
});

test('Should change locale via locale chooser', () => {
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.locale.set('de');

    const router = createRouter([], 'sulu_media.media_history');

    render(<MediaHistory resourceStore={resourceStore} router={router} />);

    const toolbarConfig = toolbarFunction.call({props: {resourceStore, router}, showSuccess: observable.box(false)});
    toolbarConfig.locale.onChange('en');

    expect(router.navigate).toBeCalledWith('sulu_media.media_history', {locale: 'en'});
});

test('Should show locales from router options in toolbar', () => {
    const resourceStore = new ResourceStore('media', 1, {locale: observable.box()});

    const router = createRouter(['en', 'de']);

    render(<MediaHistory resourceStore={resourceStore} router={router} />);

    const toolbarConfig = toolbarFunction.call({props: {resourceStore, router}, showSuccess: observable.box(false)});
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should navigate to defined route on back button click', () => {
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box('de')});

    const router = createRouter([]);

    render(<MediaHistory resourceStore={resourceStore} router={router} />);

    const toolbarConfig = toolbarFunction.call({props: {resourceStore, router}, showSuccess: observable.box(false)});
    toolbarConfig.backButton.onClick();

    expect(router.restore).toBeCalledWith('sulu_media.overview', {locale: 'de'});
});
