// @flow
/* eslint-disable react/jsx-no-bind */
import React from 'react';
import {act} from 'react-dom/test-utils';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import MultiItemSelectionComponent from '../../../components/MultiItemSelection';
import SmartContentItemComponent from '../SmartContentItem';
import FilterOverlayComponent from '../FilterOverlay';
import {translate} from '../../../utils/Translator';
import SmartContentStore from '../stores/SmartContentStore';
import smartContentConfigStore from '../stores/smartContentConfigStore';
import SmartContent from '../SmartContent';

let mockLatestMultiItemSelectionProps;

jest.mock('../../../components/MultiItemSelection', () => {
    const MultiItemSelection: any = jest.fn(function MultiItemSelection(props) {
        mockLatestMultiItemSelectionProps = props;
        function handleFilterClick() {
            props.leftButton.onClick();
        }

        return (
            <div>
                <button onClick={handleFilterClick} type="button">
                    open-filter
                </button>
                {props.children}
            </div>
        );
    });

    MultiItemSelection.Item = jest.fn(function Item(props) {
        function clickItem() {
            if (mockLatestMultiItemSelectionProps && mockLatestMultiItemSelectionProps.onItemClick) {
                mockLatestMultiItemSelectionProps.onItemClick(props.id, props.value);
            }
        }

        return (
            <button onClick={clickItem} type="button">
                {props.value.title}
                {props.children}
            </button>
        );
    });

    return MultiItemSelection;
});

jest.mock('../SmartContentItem', () => jest.fn(function SmartContentItem() {
    return <div />;
}));

jest.mock('../FilterOverlay', () => jest.fn(function FilterOverlay() {
    return <div />;
}));

jest.mock('../stores/SmartContentStore', () => jest.fn(function(provider) {
    this.provider = provider;
    this.items = [];
    this.itemsLoading = false;
    this.loading = false;
}));

jest.mock('../stores/smartContentConfigStore', () => ({
    getConfig: jest.fn(),
}));

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

function getLatestFilterOverlayProps() {
    const calls = (FilterOverlayComponent: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestMultiItemSelectionProps() {
    const calls = (MultiItemSelectionComponent: any).mock.calls;

    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
    mockLatestMultiItemSelectionProps = undefined;
    smartContentConfigStore.getConfig.mockReturnValue({
        sorting: [],
    });
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

    expect(getLatestFilterOverlayProps().sections)
        .toEqual(['tags', 'audienceTargeting', 'types', 'limit']);
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
    expect(getLatestFilterOverlayProps().sections)
        .toEqual(['datasource', 'categories', 'sorting', 'types', 'presentation']);
    expect(getLatestFilterOverlayProps().presentations)
        .toEqual({
            one: 'One column',
        });
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

    await user.click(screen.getByRole('button', {name: 'open-filter'}));
    expect(getLatestFilterOverlayProps().open).toEqual(true);

    act(() => {
        getLatestFilterOverlayProps().onClose();
    });
    expect(getLatestFilterOverlayProps().open).toEqual(false);
    expect(getLatestFilterOverlayProps().title).toEqual('sulu_admin.filter_overlay_title');
    expect(getLatestFilterOverlayProps().sortings).toEqual([{name: 'title', value: 'Title'}]);
    expect(translate).toBeCalledWith('sulu_admin.filter_overlay_title', {fieldLabel: 'Test'});
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

    expect(SmartContentItemComponent).toHaveBeenCalledTimes(2);
    expect((SmartContentItemComponent: any).mock.calls[0][0].item).toEqual({title: 'Homepage'});
    expect((SmartContentItemComponent: any).mock.calls[1][0].item).toEqual({title: 'About us'});
});

test('Call onItemClick when an item in the SmartContent is clicked', async() => {
    const user = userEvent.setup();
    const itemClickSpy = jest.fn();
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

    await user.click(screen.getByRole('button', {name: 'Homepage'}));
    expect(itemClickSpy).toHaveBeenLastCalledWith(1, {id: 1, title: 'Homepage'});

    await user.click(screen.getByRole('button', {name: 'About us'}));
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
