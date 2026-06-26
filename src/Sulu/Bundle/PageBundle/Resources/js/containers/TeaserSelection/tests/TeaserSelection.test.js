// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import TeaserSelection from '../TeaserSelection';
import TeaserStore from '../stores/TeaserStore';

let mockMultiItemSelectionProps: Object = {};
let mockMultiListOverlayProps: {[string]: Object} = {};
let mockTextEditorProps: Object = {};

const mockReact = require('react');

jest.mock('sulu-media-bundle/containers/SingleMediaSelectionOverlay', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/components', () => {
    const actual = jest.requireActual('sulu-admin-bundle/components');

    const MultiItemSelection = jest.fn((props) => {
        mockMultiItemSelectionProps = props;

        return mockReact.createElement(
            'div',
            {
                'data-disabled': String(props.disabled),
                'data-loading': String(props.loading),
                'data-testid': 'multi-item-selection',
            },
            props.leftButton && props.leftButton.options.map((option) => mockReact.createElement(
                'button',
                {
                    key: option.value,
                    onClick: () => props.leftButton.onClick(option.value),
                    type: 'button',
                },
                option.label
            )),
            props.rightButton && props.rightButton.options.map((option) => mockReact.createElement(
                'button',
                {
                    key: option.value,
                    onClick: () => props.rightButton.onClick(option.value),
                    type: 'button',
                },
                option.label
            )),
            props.onItemsSorted && mockReact.createElement(
                'button',
                {onClick: () => props.onItemsSorted(2, 1), type: 'button'},
                'sort-2-1'
            ),
            props.children
        );
    });

    (MultiItemSelection: any).Item = jest.fn((props) => mockReact.createElement(
        'div',
        {'data-testid': 'item-' + props.id},
        mockReact.createElement(
            'div',
            {
                'data-clickable': String(!!props.onClick),
                'data-testid': 'item-content-' + props.id,
                ...(props.onClick
                    ? {
                        onClick: () => props.onClick(props.id, props.value),
                        role: 'button',
                    }
                    : {}),
            },
            props.children
        ),
        props.onEdit && mockReact.createElement(
            'button',
            {onClick: () => props.onEdit(props.id), type: 'button'},
            'edit-' + props.id
        ),
        props.onRemove && mockReact.createElement(
            'button',
            {onClick: () => props.onRemove(props.id), type: 'button'},
            'remove-' + props.id
        )
    ));

    return {
        ...actual,
        MultiItemSelection,
    };
});

jest.mock('sulu-admin-bundle/containers/MultiListOverlay', () => jest.fn((props) => {
    mockMultiListOverlayProps[props.resourceKey] = props;

    return mockReact.createElement(
        'div',
        {
            'data-open': String(props.open),
            'data-testid': 'multi-list-overlay-' + props.resourceKey,
        },
        mockReact.createElement(
            'button',
            {onClick: () => props.onClose(), type: 'button'},
            'close-' + props.resourceKey
        )
    );
}));

jest.mock('sulu-admin-bundle/containers/TextEditor', () => jest.fn((props) => {
    mockTextEditorProps = props;

    return mockReact.createElement('textarea', {
        'aria-label': 'text-editor',
        onChange: (event) => props.onChange(event.currentTarget.value),
        value: props.value || '',
    });
}));

jest.mock('../stores/TeaserStore', () => jest.fn());

jest.mock('../registries/teaserProviderRegistry', () => ({
    keys: ['pages', 'articles'],
    get: jest.fn((key) => {
        switch (key) {
            case 'pages':
                return {overlayTitle: 'Pages Overlay', title: 'Pages'};
            case 'articles':
                return {overlayTitle: 'Articles Overlay', title: 'Articles'};
            case 'contacts':
                return {title: 'Contacts'};
        }
    }),
}));

function mockTeaserStore(options: Object = {}) {
    (TeaserStore: any).mockImplementation(function() {
        this.add = jest.fn();
        this.destroy = jest.fn();
        this['findById'] = options.teaserLookup || jest.fn();
        this.loading = !!options.loading;
    });
}

function getTeaserStore() {
    return (TeaserStore: any).mock.instances[0];
}

beforeEach(() => {
    mockMultiItemSelectionProps = {};
    mockMultiListOverlayProps = {};
    mockTextEditorProps = {};

    TeaserSelection.Item.mediaUrl = '/admin/media/:id?format=sulu-25x25';
    (TeaserStore: any).mockClear();
    mockTeaserStore();
});

test('Render loading teaser selection', () => {
    const value = {
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
        ],
        presentAs: '',
    };

    mockTeaserStore({loading: true});

    const {asFragment} = render(<TeaserSelection locale={observable.box('en')} onChange={jest.fn()} value={value} />);

    expect(screen.getByTestId('multi-item-selection')).toHaveAttribute('data-loading', 'true');
    expect(asFragment()).toMatchSnapshot();
});

test('Render teaser selection with presentations', () => {
    const value = {
        presentAs: 'test-2',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
        ],
    };

    const presentations = [
        {
            label: 'Test 1',
            value: 'test-1',
        },
        {
            label: 'Test 2',
            value: 'test-2',
        },
    ];

    const {asFragment} = render(
        <TeaserSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            presentations={presentations}
            value={value}
        />
    );

    expect(screen.getByRole('button', {name: 'Test 1'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'Test 2'})).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('Render teaser selection with data', () => {
    const value = {
        presentAs: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
        ],
    };

    const {asFragment} = render(<TeaserSelection locale={observable.box('en')} onChange={jest.fn()} value={value} />);

    expect(screen.getByText('Title')).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('Render MultiItemSelection disabled when disabled flag is set', () => {
    render(<TeaserSelection disabled={true} locale={observable.box('en')} onChange={jest.fn()} />);

    expect(screen.getByTestId('multi-item-selection')).toHaveAttribute('data-disabled', 'true');
});

test('Avoid that MultiListOverlay loads the preSelectedItems from start', () => {
    render(<TeaserSelection disabled={true} locale={observable.box('en')} onChange={jest.fn()} />);

    expect(Object.keys(mockMultiListOverlayProps)).toEqual(['pages', 'articles']);
    expect(mockMultiListOverlayProps.pages.preloadSelectedItems).toEqual(false);
    expect(mockMultiListOverlayProps.articles.preloadSelectedItems).toEqual(false);
});

test('Call onChange when presentation is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const presentations = [
        {
            label: 'Test 1',
            value: 'test-1',
        },
        {
            label: 'Test 2',
            value: 'test-2',
        },
    ];

    render(
        <TeaserSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            presentations={presentations}
            value={undefined}
        />
    );

    await user.click(screen.getByRole('button', {name: 'Test 2'}));

    expect(changeSpy).toHaveBeenCalledWith({
        presentAs: 'test-2',
        items: [],
    });
});

test('Add passed data to TeaserStore', () => {
    const value = {
        presentAs: '',
        items: [
            {
                description: 'Description 1',
                id: 2,
                title: 'Title 1',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 3,
                title: 'Title 2',
                type: 'contacts',
            },
        ],
    };

    render(<TeaserSelection locale={observable.box('en')} onChange={jest.fn()} value={value} />);

    const teaserStore = getTeaserStore();
    expect(teaserStore.add).toHaveBeenCalledTimes(2);
    expect(teaserStore.add).toHaveBeenCalledWith('pages', 2);
    expect(teaserStore.add).toHaveBeenCalledWith('contacts', 3);
});

test('Load combined data from TeaserStore and props', () => {
    const value = {
        presentAs: '',
        items: [
            {
                description: 'Edited Page Description',
                id: 2,
                title: 'Edited Page Title',
                type: 'pages',
            },
            {
                description: undefined,
                id: 3,
                title: undefined,
                type: 'contacts',
            },
            {
                id: 4,
                type: 'contacts',
            },
        ],
    };

    mockTeaserStore({
        teaserLookup: jest.fn((type, id) => {
            if (type === 'pages' && id === 2) {
                return {
                    description: 'Page Description',
                    id: 2,
                    mediaId: 8,
                    title: 'Page',
                    type: 'pages',
                };
            }

            if (type === 'contacts' && id === 3) {
                return {
                    description: 'Contact Description 1',
                    id: 3,
                    title: 'Contact 1',
                    type: 'contacts',
                };
            }

            if (type === 'contacts' && id === 4) {
                return {
                    description: 'Contact Description 2',
                    id: 4,
                    title: 'Contact 2',
                    type: 'contacts',
                };
            }

            throw new Error('This case should not happen!');
        }),
    });

    const {asFragment} = render(<TeaserSelection locale={observable.box('en')} onChange={jest.fn()} value={value} />);

    expect(screen.getByText('Edited Page Title')).toBeInTheDocument();
    expect(screen.getByText('Contact 1')).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('Opening different adding overlays and close them without any action', async() => {
    const user = userEvent.setup();

    render(<TeaserSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />);

    expect(mockMultiItemSelectionProps.leftButton.options).toEqual([
        {label: 'Pages', value: 'pages'},
        {label: 'Articles', value: 'articles'},
    ]);
    expect(screen.getByTestId('multi-list-overlay-pages')).toHaveAttribute('data-open', 'false');
    expect(screen.getByTestId('multi-list-overlay-articles')).toHaveAttribute('data-open', 'false');

    await user.click(screen.getByRole('button', {name: 'Articles'}));

    expect(screen.getByTestId('multi-list-overlay-pages')).toHaveAttribute('data-open', 'false');
    expect(screen.getByTestId('multi-list-overlay-articles')).toHaveAttribute('data-open', 'true');

    await user.click(screen.getByRole('button', {name: 'close-articles'}));
    await user.click(screen.getByRole('button', {name: 'Pages'}));

    expect(screen.getByTestId('multi-list-overlay-pages')).toHaveAttribute('data-open', 'true');
    expect(screen.getByTestId('multi-list-overlay-articles')).toHaveAttribute('data-open', 'false');
});

test('Adding a teaser element', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    render(<TeaserSelection locale={observable.box('en')} onChange={changeSpy} value={undefined} />);

    await user.click(screen.getByRole('button', {name: 'Pages'}));

    expect(screen.getByTestId('multi-list-overlay-pages')).toHaveAttribute('data-open', 'true');

    act(() => {
        mockMultiListOverlayProps.pages.onConfirm([{id: 6}, {id: 5}]);
    });

    expect(screen.getByTestId('multi-list-overlay-pages')).toHaveAttribute('data-open', 'false');
    expect(changeSpy).toHaveBeenCalledWith({
        presentAs: undefined,
        items: [{id: 6, type: 'pages'}, {id: 5, type: 'pages'}],
    });

    const teaserStore = getTeaserStore();
    expect(teaserStore.add).toHaveBeenCalledWith('pages', 6);
    expect(teaserStore.add).toHaveBeenCalledWith('pages', 5);
});

test('Adding two different kind of teasers', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const value = {
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
            {id: 8, type: 'pages'},
        ],
    };

    render(<TeaserSelection locale={observable.box('en')} onChange={changeSpy} value={value} />);

    await user.click(screen.getByRole('button', {name: 'Articles'}));

    expect(screen.getByTestId('multi-list-overlay-articles')).toHaveAttribute('data-open', 'true');

    act(() => {
        mockMultiListOverlayProps.articles.onConfirm([{id: 6}]);
    });

    expect(screen.getByTestId('multi-list-overlay-articles')).toHaveAttribute('data-open', 'false');
    expect(changeSpy).toHaveBeenCalledWith({
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
            {id: 8, type: 'pages'},
            {id: 6, type: 'articles'},
        ],
    });

    expect(getTeaserStore().add).toHaveBeenCalledWith('articles', 6);
});

test('Adding a teaser item along with other teaser items which has already been added', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const value = {
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
        ],
    };

    render(<TeaserSelection locale={observable.box('en')} onChange={changeSpy} value={value} />);

    await user.click(screen.getByRole('button', {name: 'Pages'}));

    expect(screen.getByTestId('multi-list-overlay-pages')).toHaveAttribute('data-open', 'true');

    act(() => {
        mockMultiListOverlayProps.pages.onConfirm([{id: 5}, {id: 6}]);
    });

    expect(screen.getByTestId('multi-list-overlay-pages')).toHaveAttribute('data-open', 'false');
    expect(changeSpy).toHaveBeenCalledWith({
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
            {id: 6, type: 'pages'},
        ],
    });

    expect(getTeaserStore().add).toHaveBeenCalledWith('pages', 6);
});

test('Removing by unselecting element in teaser selection', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const value = {
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
            {id: 8, type: 'pages'},
            {id: 5, type: 'articles'},
        ],
    };

    render(<TeaserSelection locale={observable.box('en')} onChange={changeSpy} value={value} />);

    await user.click(screen.getByRole('button', {name: 'Pages'}));

    expect(screen.getByTestId('multi-list-overlay-pages')).toHaveAttribute('data-open', 'true');

    act(() => {
        mockMultiListOverlayProps.pages.onConfirm([{id: 6}]);
    });

    expect(screen.getByTestId('multi-list-overlay-pages')).toHaveAttribute('data-open', 'false');
    expect(changeSpy).toHaveBeenCalledWith({
        presentAs: undefined,
        items: [{id: 5, type: 'articles'}, {id: 6, type: 'pages'}],
    });

    const teaserStore = getTeaserStore();
    expect(teaserStore.add).toHaveBeenCalledWith('pages', 6);
    expect(teaserStore.add).toHaveBeenCalledWith('pages', 5);
});

test('Preselecting correct items', async() => {
    const user = userEvent.setup();

    const value = {
        presentAs: undefined,
        items: [
            {id: 5, type: 'pages'},
            {id: 8, type: 'pages'},
            {id: 5, type: 'articles'},
        ],
    };

    render(<TeaserSelection locale={observable.box('en')} onChange={jest.fn()} value={value} />);

    await user.click(screen.getByRole('button', {name: 'Pages'}));

    expect(screen.getByTestId('multi-list-overlay-pages')).toHaveAttribute('data-open', 'true');
    expect(mockMultiListOverlayProps.pages.preSelectedItems)
        .toEqual([{id: 5, type: 'pages'}, {id: 8, type: 'pages'}]);
});

test('Open and close items when clicking on the pen icon', async() => {
    const user = userEvent.setup();

    const value = {
        presentAs: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 6,
                title: 'Title 2',
                type: 'pages',
            },
        ],
    };

    render(<TeaserSelection locale={observable.box('en')} onChange={jest.fn()} value={value} />);

    expect(screen.queryByDisplayValue('Title')).not.toBeInTheDocument();
    expect(screen.queryByDisplayValue('Title 2')).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'edit-pages;2'}));

    expect(screen.getByDisplayValue('Title')).toBeInTheDocument();
    expect(screen.queryByDisplayValue('Title 2')).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'edit-pages;6'}));

    expect(screen.getByDisplayValue('Title')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Title 2')).toBeInTheDocument();

    await user.click(screen.getAllByRole('button', {name: 'sulu_admin.cancel'})[0]);

    expect(screen.queryByDisplayValue('Title')).not.toBeInTheDocument();
    expect(screen.getByDisplayValue('Title 2')).toBeInTheDocument();
});

test('Call onChange with new values when apply button is clicked', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const value = {
        presentAs: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 6,
                title: 'Title 2',
                type: 'pages',
            },
            {
                description: 'Description 3',
                id: 6,
                title: 'Title 3',
                type: 'contacts',
            },
        ],
    };

    render(<TeaserSelection locale={observable.box('en')} onChange={changeSpy} value={value} />);

    await user.click(screen.getByRole('button', {name: 'edit-pages;6'}));
    await user.clear(screen.getByDisplayValue('Title 2'));
    await user.type(screen.getByRole('textbox', {name: ''}), 'Edited Title 2');
    await user.clear(screen.getByLabelText('text-editor'));
    await user.type(screen.getByLabelText('text-editor'), 'Edited Description 2');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.apply'}));

    expect(mockTextEditorProps.adapter).toEqual('ckeditor5');
    expect(changeSpy).toHaveBeenCalledWith(
        {
            presentAs: '',
            items: [
                {
                    description: 'Description',
                    id: 2,
                    title: 'Title',
                    type: 'pages',
                },
                {
                    description: 'Edited Description 2',
                    id: 6,
                    mediaId: undefined,
                    title: 'Edited Title 2',
                    type: 'pages',
                },
                {
                    description: 'Description 3',
                    id: 6,
                    title: 'Title 3',
                    type: 'contacts',
                },
            ],
        }
    );
});

test('Call onChange with new values after one item is removed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const value = {
        presentAs: '',
        items: [
            {
                description: 'Contact',
                id: 6,
                title: 'Contact',
                type: 'contacts',
            },
            {
                description: 'Description 2',
                id: 6,
                title: 'Title 2',
                type: 'pages',
            },
            {
                description: 'Description 3',
                id: 7,
                title: 'Title 3',
                type: 'pages',
            },
        ],
    };

    render(<TeaserSelection locale={observable.box('en')} onChange={changeSpy} value={value} />);

    await user.click(screen.getByRole('button', {name: 'remove-pages;6'}));

    expect(changeSpy).toHaveBeenCalledWith(
        {
            presentAs: '',
            items: [
                {
                    description: 'Contact',
                    id: 6,
                    title: 'Contact',
                    type: 'contacts',
                },
                {
                    description: 'Description 3',
                    id: 7,
                    title: 'Title 3',
                    type: 'pages',
                },
            ],
        }
    );
});

test('Call onChange with new values after items are sorted', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const value = {
        presentAs: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 6,
                title: 'Title 2',
                type: 'pages',
            },
            {
                description: 'Description 3',
                id: 9,
                title: 'Title 3',
                type: 'pages',
            },
        ],
    };

    render(<TeaserSelection locale={observable.box('en')} onChange={changeSpy} value={value} />);

    await user.click(screen.getByRole('button', {name: 'sort-2-1'}));

    expect(changeSpy).toHaveBeenCalledWith(
        {
            presentAs: '',
            items: [
                {
                    description: 'Description',
                    id: 2,
                    title: 'Title',
                    type: 'pages',
                },
                {
                    description: 'Description 3',
                    id: 9,
                    title: 'Title 3',
                    type: 'pages',
                },
                {
                    description: 'Description 2',
                    id: 6,
                    title: 'Title 2',
                    type: 'pages',
                },
            ],
        }
    );
});

test('Call onItemClick when an item is clicked', async() => {
    const user = userEvent.setup();
    const itemClickSpy = jest.fn();

    const item1 = {
        description: 'Description',
        edited: true,
        id: 2,
        title: 'Title',
        type: 'pages',
    };

    const item2 = {
        description: 'Description 2',
        edited: true,
        id: 6,
        title: 'Title 2',
        type: 'pages',
    };

    const value = {
        presentAs: '',
        items: [
            item1,
            item2,
        ],
    };

    render(
        <TeaserSelection locale={observable.box('en')} onChange={jest.fn()} onItemClick={itemClickSpy} value={value} />
    );

    await user.click(screen.getByTestId('item-content-pages;2'));
    expect(itemClickSpy).toHaveBeenLastCalledWith('pages;2', item1);
    await user.click(screen.getByTestId('item-content-pages;6'));
    expect(itemClickSpy).toHaveBeenLastCalledWith('pages;6', item2);
});

test('Call not onItemClick when an item is clicked in edit mode', async() => {
    const user = userEvent.setup();
    const itemClickSpy = jest.fn();

    const item1 = {
        description: 'Description',
        edited: true,
        id: 2,
        title: 'Title',
        type: 'pages',
    };

    const item2 = {
        description: 'Description 2',
        edited: true,
        id: 6,
        title: 'Title 2',
        type: 'pages',
    };

    const value = {
        presentAs: '',
        items: [
            item1,
            item2,
        ],
    };

    render(
        <TeaserSelection locale={observable.box('en')} onChange={jest.fn()} onItemClick={itemClickSpy} value={value} />
    );

    await user.click(screen.getByRole('button', {name: 'edit-pages;2'}));

    expect(screen.getByDisplayValue('Title')).toBeInTheDocument();
    expect(screen.getByTestId('item-content-pages;2')).toHaveAttribute('data-clickable', 'false');
    expect(itemClickSpy).toHaveBeenCalledTimes(0);
});

test('Call destroy of TeaserStore when unmounted', () => {
    const {unmount} = render(<TeaserSelection locale={observable.box('en')} onChange={jest.fn()} />);

    const teaserStore = getTeaserStore();

    unmount();

    expect(teaserStore.destroy).toHaveBeenCalledWith();
});
