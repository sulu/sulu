/* eslint-disable flowtype/require-valid-file-annotation */
import copyToClipboard from 'copy-to-clipboard';
import React from 'react';
import {act, render, screen, waitFor, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import formatStore from '../../../stores/formatStore';
import MediaFormats from '../MediaFormats';

const mockToolbarConfigGetters = [];

jest.mock('copy-to-clipboard', () => jest.fn());

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');

    const Loader = jest.fn(function LoaderMock() {
        return React.createElement('div', {'data-testid': 'loader'});
    });

    const Table = jest.fn(function TableMock({children}) {
        return React.createElement('table', {'data-testid': 'media-formats-table'}, children);
    });

    Table.Header = jest.fn(function HeaderMock({children}) {
        return React.createElement('thead', null, children);
    });

    Table.HeaderCell = jest.fn(function HeaderCellMock({children}) {
        return React.createElement('th', null, children);
    });

    Table.Body = jest.fn(function BodyMock({children}) {
        return React.createElement('tbody', null, children);
    });

    Table.Row = jest.fn(function RowMock({buttons = [], children, id}) {
        return React.createElement(
            'tr',
            {'data-testid': 'row-' + id},
            ...buttons.map((button, index) => React.createElement(
                'td',
                {key: index},
                React.createElement(
                    'button',
                    {
                        onClick: () => button.onClick && button.onClick(id),
                        type: 'button',
                    },
                    button.icon
                )
            )),
            children
        );
    });

    Table.Cell = jest.fn(function CellMock({children}) {
        return React.createElement('td', null, children);
    });

    return {
        Loader,
        Table,
    };
});

jest.mock('sulu-admin-bundle/containers', () => ({
    withToolbar: jest.fn((Component, toolbar) => {
        return class WithToolbarMock extends Component {
            render() {
                mockToolbarConfigGetters.push(() => toolbar.call(this));

                return super.render();
            }
        };
    }),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id, observableOptions = {}) {
        this.locale = observableOptions.locale;
        this.data = {
            thumbnails: {},
        };
        this.loading = false;
    }),
}));

jest.mock('../../../stores/formatStore', () => ({
    loadFormats: jest.fn(),
}));

function createRouter(locales = [], routeName = 'sulu_media.media_formats') {
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

function createResourceStore(locale = undefined) {
    return new ResourceStore('media', '1', {locale: locale ? observable.box(locale) : observable.box()});
}

function getLatestToolbarConfig() {
    return mockToolbarConfigGetters[mockToolbarConfigGetters.length - 1]();
}

beforeEach(() => {
    jest.clearAllMocks();
    mockToolbarConfigGetters.splice(0, mockToolbarConfigGetters.length);
    formatStore.loadFormats.mockReturnValue(new Promise(() => {}));
});

afterEach(() => {
    jest.useRealTimers();
});

test('Render a loading MediaFormats view', () => {
    const router = createRouter([]);
    const resourceStore = createResourceStore();
    resourceStore.loading = true;

    const {asFragment} = render(
        <MediaFormats resourceStore={resourceStore} router={router} title="Test 1" />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a loading MediaFormats view if formats have not been loaded yet', () => {
    const router = createRouter([]);
    const resourceStore = createResourceStore();
    resourceStore.loading = false;

    const {asFragment} = render(
        <MediaFormats resourceStore={resourceStore} router={router} />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaFormats view', async() => {
    formatStore.loadFormats.mockReturnValue(Promise.resolve([
        {
            key: '400x400',
            title: 'Contact',
        },
        {
            key: '800x800',
            title: 'Account',
        },
    ]));

    const router = createRouter([]);
    const resourceStore = createResourceStore();
    resourceStore.data.thumbnails = {
        '400x400': '/media/400x400/image.jpg',
        '800x800': '/media/800x800/image.jpg',
    };

    const {asFragment} = render(<MediaFormats resourceStore={resourceStore} router={router} title="Test 2" />);

    expect(await screen.findByText('Contact')).toBeInTheDocument();

    expect(asFragment()).toMatchSnapshot();
});

test('Open the image in the given format when icon is clicked', async() => {
    formatStore.loadFormats.mockReturnValue(Promise.resolve([
        {
            key: '400x400',
        },
        {
            key: '800x800',
        },
    ]));

    window.open = jest.fn();

    const router = createRouter([]);
    const resourceStore = createResourceStore();
    resourceStore.data.thumbnails = {
        '400x400': '/media/400x400/image.jpg?v=1',
        '800x800': '/media/800x800/image.jpg?v=1',
    };

    const user = userEvent.setup();

    render(<MediaFormats resourceStore={resourceStore} router={router} />);

    expect(await screen.findByTestId('row-400x400')).toBeInTheDocument();

    await user.click(screen.getAllByRole('button', {name: 'su-eye'})[0]);
    expect(window.open).toHaveBeenLastCalledWith('/media/400x400/image.jpg?v=1&inline=1');

    await user.click(screen.getAllByRole('button', {name: 'su-eye'})[1]);
    expect(window.open).toHaveBeenLastCalledWith('/media/800x800/image.jpg?v=1&inline=1');
});

test('Copy the image URL for the given format when icon is clicked and show a success message', async() => {
    jest.useFakeTimers();

    formatStore.loadFormats.mockReturnValue(Promise.resolve([
        {
            key: '400x400',
        },
        {
            key: '800x800',
        },
    ]));

    const router = createRouter([]);
    const resourceStore = createResourceStore();
    resourceStore.data.thumbnails = {
        '400x400': '/media/400x400/image.jpg?v=1',
        '800x800': '/media/800x800/image.jpg?v=1',
    };

    const user = userEvent.setup({advanceTimers: jest.advanceTimersByTime});

    render(<MediaFormats resourceStore={resourceStore} router={router} />);

    expect(await screen.findByTestId('row-400x400')).toBeInTheDocument();

    await user.click(within(screen.getByTestId('row-400x400')).getByRole('button', {name: 'su-copy'}));
    expect(copyToClipboard).toHaveBeenLastCalledWith('http://localhost/media/400x400/image.jpg?v=1');
    expect(within(screen.getByTestId('row-400x400')).getByRole('button', {name: 'su-check'})).toBeInTheDocument();

    act(() => {
        jest.runAllTimers();
    });

    await waitFor(() => {
        expect(within(screen.getByTestId('row-400x400')).getByRole('button', {name: 'su-copy'})).toBeInTheDocument();
    });

    await user.click(within(screen.getByTestId('row-800x800')).getByRole('button', {name: 'su-copy'}));
    expect(copyToClipboard).toHaveBeenLastCalledWith('http://localhost/media/800x800/image.jpg?v=1');
    expect(within(screen.getByTestId('row-800x800')).getByRole('button', {name: 'su-check'})).toBeInTheDocument();

    act(() => {
        jest.runAllTimers();
    });

    await waitFor(() => {
        expect(within(screen.getByTestId('row-800x800')).getByRole('button', {name: 'su-copy'})).toBeInTheDocument();
    });
});

test('Should change locale via locale chooser', () => {
    formatStore.loadFormats.mockReturnValue(Promise.resolve());

    const resourceStore = createResourceStore('de');

    const router = createRouter([], 'sulu_media.media_formats');

    render(<MediaFormats resourceStore={resourceStore} router={router} />);

    const toolbarConfig = getLatestToolbarConfig();
    toolbarConfig.locale.onChange('en');

    expect(router.navigate).toBeCalledWith('sulu_media.media_formats', {locale: 'en'});
});

test('Should show locales from router options in toolbar', () => {
    formatStore.loadFormats.mockReturnValue(Promise.resolve());

    const resourceStore = createResourceStore();

    const router = createRouter(['en', 'de']);

    render(<MediaFormats resourceStore={resourceStore} router={router} />);

    const toolbarConfig = getLatestToolbarConfig();

    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should navigate to defined route on back button click', () => {
    formatStore.loadFormats.mockReturnValue(Promise.resolve());

    const resourceStore = createResourceStore('de');

    const router = createRouter([]);

    render(<MediaFormats resourceStore={resourceStore} router={router} />);

    const toolbarConfig = getLatestToolbarConfig();
    toolbarConfig.backButton.onClick();

    expect(router.restore).toBeCalledWith('sulu_media.overview', {locale: 'de'});
});
