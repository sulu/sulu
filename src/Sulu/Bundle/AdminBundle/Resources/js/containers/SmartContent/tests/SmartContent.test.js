// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {translate} from '../../../utils/Translator';
import SmartContentStore from '../stores/SmartContentStore';
import smartContentConfigStore from '../stores/smartContentConfigStore';
import SmartContent from '../SmartContent';
import getMockCallArg from '../../../utils/TestHelper/getMockCallArg';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../stores/SmartContentStore', () => jest.fn(function() {
    this.items = [];
}));

jest.mock('../stores/smartContentConfigStore', () => ({
    getConfig: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../FilterOverlay', () => {
    const React = require('react');

    return jest.fn(function FilterOverlayMock({onClose, open}) {
        return React.createElement(
            'div',
            {'data-open': open ? 'true' : 'false', 'data-testid': 'filter-overlay'},
            React.createElement('button', {onClick: onClose, type: 'button'}, 'close-filter')
        );
    });
});

jest.mock('../../../components/MultiItemSelection', () => {
    const React = require('react');

    const MultiItemSelection: any = jest.fn(function MultiItemSelectionMock({children, leftButton, onItemClick}) {
        function handleFilterClick() {
            leftButton.onClick();
        }

        const itemButtons = React.Children.map(children, (child, index) => {
            function handleItemClick() {
                if (onItemClick) {
                    onItemClick(child.props.id, child.props.value);
                }
            }

            return React.createElement(
                'button',
                {
                    'data-testid': 'multi-item-content',
                    key: index,
                    onClick: handleItemClick,
                    type: 'button',
                },
                child.props.children
            );
        });

        return React.createElement(
            'div',
            {'data-testid': 'multi-item-selection'},
            React.createElement('button', {onClick: handleFilterClick, type: 'button'}, 'filter-button'),
            itemButtons
        );
    });

    MultiItemSelection.Item = jest.fn(function MultiItemSelectionItemMock({children}) {
        return React.createElement(React.Fragment, undefined, children);
    });

    return MultiItemSelection;
});

jest.mock('../SmartContentItem', () => {
    const React = require('react');

    return jest.fn(function SmartContentItemMock() {
        return React.createElement('div', {'data-testid': 'smart-content-item'});
    });
});

const multiItemSelection = (jest.requireMock('../../../components/MultiItemSelection'): any);
const filterOverlay = (jest.requireMock('../FilterOverlay'): any);
const smartContentItem = (jest.requireMock('../SmartContentItem'): any);

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

function getLatestFilterOverlayProps(): any {
    return getLatestMockProps(filterOverlay);
}

function getLatestMultiItemSelectionProps(): any {
    return getLatestMockProps(multiItemSelection);
}

beforeEach(() => {
    jest.clearAllMocks();
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

    const smartContentStore = new SmartContentStore('content');
    render(
        <SmartContent
            defaultValue={defaultValue}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(getLatestFilterOverlayProps().sections).toEqual(['tags', 'audienceTargeting', 'types', 'limit']);
});

test('Disable sorting on MultiItemSelection', () => {
    smartContentConfigStore.getConfig.mockReturnValue({
        tags: true,
        categories: false,
        audienceTargeting: true,
        sorting: [],
        presentAs: false,
        limit: true,
        types: [],
    });

    const smartContentStore = new SmartContentStore('content');
    render(
        <SmartContent
            defaultValue={defaultValue}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(getLatestMultiItemSelectionProps().sortable).toEqual(false);
});

test('Pass correct props to MultiItemSelection component', () => {
    smartContentConfigStore.getConfig.mockReturnValue({
        tags: true,
        categories: false,
        audienceTargeting: true,
        sorting: [],
        presentAs: false,
        limit: true,
        types: [],
    });

    const smartContentStore = new SmartContentStore('content');
    render(
        <SmartContent
            defaultValue={defaultValue}
            disabled={true}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(getLatestMultiItemSelectionProps().disabled).toEqual(true);
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

    const presentations = [
        {name: 'one', value: 'One column'},
    ];

    const smartContentStore = new SmartContentStore('content');
    render(
        <SmartContent
            categoryRootKey="test1"
            defaultValue={defaultValue}
            fieldLabel="Test"
            presentations={presentations}
            store={smartContentStore}
        />
    );

    expect(getLatestFilterOverlayProps().categoryRootKey).toEqual('test1');
    expect(getLatestFilterOverlayProps().dataSourceListKey).toEqual('pages_list');
    expect(getLatestFilterOverlayProps().dataSourceResourceKey).toEqual('pages');
    expect(getLatestFilterOverlayProps().sections).toEqual([
        'datasource',
        'categories',
        'sorting',
        'types',
        'presentation',
    ]);
    expect(getLatestFilterOverlayProps().presentations).toEqual({one: 'One column'});
});

test('Open and closes the FilterOverlay when the icon is clicked', async() => {
    const user = userEvent.setup();
    const smartContentStore = new SmartContentStore('content');
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

    render(
        <SmartContent
            defaultValue={defaultValue}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(getLatestFilterOverlayProps().open).toEqual(false);
    await user.click(screen.getByRole('button', {name: 'filter-button'}));
    expect(getLatestFilterOverlayProps().open).toEqual(true);

    getLatestFilterOverlayProps().onClose();
    expect(getLatestFilterOverlayProps().open).toEqual(false);
    expect(getLatestFilterOverlayProps().title).toEqual('sulu_admin.filter_overlay_title');
    expect(getLatestFilterOverlayProps().sortings).toEqual([{name: 'title', value: 'Title'}]);
    expect(translate).toHaveBeenCalledWith('sulu_admin.filter_overlay_title', {fieldLabel: 'Test'});
});

test('Show items in a SmartContentItem', () => {
    const smartContentStore = new SmartContentStore('content');
    smartContentStore.items = [
        {title: 'Homepage'},
        {title: 'About us'},
    ];

    render(
        <SmartContent
            defaultValue={defaultValue}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(screen.getAllByTestId('smart-content-item')).toHaveLength(2);
    expect(getMockCallArg(smartContentItem, 0, 0).item).toEqual({title: 'Homepage'});
    expect(getMockCallArg(smartContentItem, 1, 0).item).toEqual({title: 'About us'});
});

test('Call onItemClick when an item in the SmartContent is clicked', async() => {
    const itemClickSpy = jest.fn();
    const user = userEvent.setup();
    const smartContentStore = new SmartContentStore('content');
    smartContentStore.items = [
        {id: 1, title: 'Homepage'},
        {id: 2, title: 'About us'},
    ];

    render(
        <SmartContent
            defaultValue={defaultValue}
            fieldLabel="Test"
            onItemClick={itemClickSpy}
            store={smartContentStore}
        />
    );

    const items = screen.getAllByTestId('multi-item-content');
    await user.click(items[0]);
    expect(itemClickSpy).toHaveBeenLastCalledWith(1, {id: 1, title: 'Homepage'});

    await user.click(items[1]);
    expect(itemClickSpy).toHaveBeenLastCalledWith(2, {id: 2, title: 'About us'});
});

test('Pass the loading prop to the MultiItemSelection if items are still loading', () => {
    const smartContentStore = new SmartContentStore('content');
    smartContentStore.itemsLoading = true;

    render(
        <SmartContent
            defaultValue={defaultValue}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(getLatestMultiItemSelectionProps().loading).toEqual(true);
});

test('Pass the loading prop to the MultiItemSelection if list or categories are still loading', () => {
    const smartContentStore = new SmartContentStore('content');
    // $FlowFixMe
    smartContentStore.loading = true;

    render(
        <SmartContent
            defaultValue={defaultValue}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(getLatestMultiItemSelectionProps().loading).toEqual(true);
});
