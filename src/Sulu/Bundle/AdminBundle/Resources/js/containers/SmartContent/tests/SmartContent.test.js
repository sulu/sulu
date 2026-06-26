// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {translate} from '../../../utils/Translator';
import SmartContentStore from '../stores/SmartContentStore';
import smartContentConfigStore from '../stores/smartContentConfigStore';
import SmartContent from '../SmartContent';

const mockReact = require('react');
let mockFilterOverlayProps: Object = {};

jest.mock('../stores/SmartContentStore', () => jest.fn(function(provider) {
    this.items = [];
    this.itemsLoading = false;
    this.loading = false;
    this.provider = provider;
}));

jest.mock('../stores/smartContentConfigStore', () => ({
    getConfig: jest.fn(),
}));

jest.mock('../FilterOverlay', () => jest.fn((props) => {
    mockFilterOverlayProps = props;

    return mockReact.createElement(
        'div',
        {
            'data-open': props.open ? 'true' : 'false',
            'data-testid': 'filter-overlay',
        },
        mockReact.createElement(
            'button',
            {
                'aria-label': 'filter-overlay-close',
                onClick: props.onClose,
                type: 'button',
            },
            'Close'
        )
    );
}));

jest.mock('../../../utils/Translator');

const defaultValue = {
    dataSource: undefined,
    includeSubFolders: false,
    categories: undefined,
    categoryOperator: 'or',
    tags: undefined,
    tagOperator: 'or',
    audienceTargeting: false,
    sortBy: 'title',
    sortMethod: 'asc',
    presentAs: undefined,
    limitResult: undefined,
    types: undefined,
};

const defaultConfig = {
    tags: false,
    categories: false,
    audienceTargeting: false,
    sorting: [],
    presentAs: false,
    limit: false,
    types: [],
};

function renderSmartContent(props: Object = {}) {
    const smartContentStore = props.store || new SmartContentStore('content');

    return {
        smartContentStore,
        ...render(
            <SmartContent
                defaultValue={defaultValue}
                fieldLabel="Test"
                store={smartContentStore}
                {...props}
            />
        ),
    };
}

function getButtonByIcon(icon: string): HTMLButtonElement {
    const iconElement = screen.getByLabelText(icon);
    const button = iconElement.parentElement;

    if (!(button instanceof HTMLButtonElement)) {
        throw new Error('Button with icon "' + icon + '" was not rendered.');
    }

    return button;
}

function getItemButton(title: string): HTMLElement {
    const titleElement = screen.getByText(title);
    const button = titleElement.closest('[role="button"]');

    if (!(button instanceof HTMLElement)) {
        throw new Error('Item with title "' + title + '" was not rendered.');
    }

    return button;
}

beforeEach(() => {
    jest.clearAllMocks();
    mockFilterOverlayProps = {};
    smartContentConfigStore.getConfig.mockReturnValue(defaultConfig);
});

test('Pass correct sections prop', () => {
    smartContentConfigStore.getConfig.mockReturnValue({
        tags: true,
        categories: false,
        audienceTargeting: true,
        sorting: [],
        presentAs: false,
        limit: true,
        types: [
            {name: 'default', value: 'default'},
            {name: 'homepage', value: 'homepage'},
        ],
    });

    renderSmartContent();

    expect(mockFilterOverlayProps.sections).toEqual(['tags', 'audienceTargeting', 'types', 'limit']);
});

test('Disable sorting on MultiItemSelection', () => {
    const smartContentStore = new SmartContentStore('content');
    smartContentStore.items = [{id: 1, title: 'Homepage'}];

    renderSmartContent({store: smartContentStore});

    expect(screen.getByText('Homepage')).toBeInTheDocument();
    expect(screen.queryByLabelText('su-more')).not.toBeInTheDocument();
});

test('Pass correct props to MultiItemSelection component', () => {
    renderSmartContent({disabled: true});

    expect(getButtonByIcon('su-filter')).toBeDisabled();
});

test('Pass correct sections prop with other values', () => {
    smartContentConfigStore.getConfig.mockReturnValue({
        datasourceListKey: 'pages_list',
        datasourceResourceKey: 'pages',
        datasourceAdapter: 'table',
        tags: false,
        categories: true,
        audienceTargeting: false,
        sorting: [{name: 'title', value: 'Title'}],
        presentAs: true,
        limit: false,
        types: [
            {name: 'default', value: 'default'},
            {name: 'homepage', value: 'homepage'},
        ],
    });

    renderSmartContent({
        categoryRootKey: 'test1',
        presentations: [
            {name: 'one', value: 'One column'},
        ],
    });

    expect(mockFilterOverlayProps.categoryRootKey).toEqual('test1');
    expect(mockFilterOverlayProps.dataSourceListKey).toEqual('pages_list');
    expect(mockFilterOverlayProps.dataSourceResourceKey).toEqual('pages');
    expect(mockFilterOverlayProps.sections)
        .toEqual(['datasource', 'categories', 'sorting', 'types', 'presentation']);
    expect(mockFilterOverlayProps.presentations)
        .toEqual({
            one: 'One column',
        });
});

test('Open and closes the FilterOverlay when the icon is clicked', async() => {
    const user = userEvent.setup();
    smartContentConfigStore.getConfig.mockReturnValue({
        datasourceResourceKey: 'pages',
        datasourceAdapter: 'table',
        tags: false,
        categories: true,
        audienceTargeting: false,
        sorting: [
            {name: 'title', value: 'Title'},
        ],
        presentAs: true,
        limit: false,
        types: [],
    });

    renderSmartContent();

    expect(screen.getByTestId('filter-overlay')).toHaveAttribute('data-open', 'false');

    await user.click(getButtonByIcon('su-filter'));
    expect(screen.getByTestId('filter-overlay')).toHaveAttribute('data-open', 'true');

    await user.click(screen.getByLabelText('filter-overlay-close'));
    expect(screen.getByTestId('filter-overlay')).toHaveAttribute('data-open', 'false');
    expect(mockFilterOverlayProps.title).toEqual('sulu_admin.filter_overlay_title');
    expect(mockFilterOverlayProps.sortings).toEqual([{name: 'title', value: 'Title'}]);
    expect(translate).toHaveBeenCalledWith('sulu_admin.filter_overlay_title', {fieldLabel: 'Test'});
});

test('Show items in a SmartContentItem', () => {
    const smartContentStore = new SmartContentStore('content');
    smartContentStore.items = [
        {title: 'Homepage'},
        {title: 'About us'},
    ];

    renderSmartContent({store: smartContentStore});

    expect(screen.getByText('Homepage')).toBeInTheDocument();
    expect(screen.getByText('About us')).toBeInTheDocument();
});

test('Call onItemClick when an item in the SmartContent is clicked', async() => {
    const user = userEvent.setup();
    const itemClickSpy = jest.fn();
    const smartContentStore = new SmartContentStore('content');
    smartContentStore.items = [
        {id: 1, title: 'Homepage'},
        {id: 2, title: 'About us'},
    ];

    renderSmartContent({
        onItemClick: itemClickSpy,
        store: smartContentStore,
    });

    await user.click(getItemButton('Homepage'));
    expect(itemClickSpy).toHaveBeenLastCalledWith(1, {id: 1, title: 'Homepage'});

    await user.click(getItemButton('About us'));
    expect(itemClickSpy).toHaveBeenLastCalledWith(2, {id: 2, title: 'About us'});
});

test('Pass the loading prop to the MultiItemSelection if items are still loading', () => {
    const smartContentStore = new SmartContentStore('content');
    smartContentStore.itemsLoading = true;

    renderSmartContent({store: smartContentStore});

    expect(document.querySelector('.loader')).toBeInTheDocument();
});

test('Pass the loading prop to the MultiItemSelection if list or categories are still loading', () => {
    const smartContentStore = new SmartContentStore('content');
    (smartContentStore: any).loading = true;

    renderSmartContent({store: smartContentStore});

    expect(document.querySelector('.loader')).toBeInTheDocument();
});
