// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {translate} from '../../../utils/Translator';
import SmartContentStore from '../stores/SmartContentStore';
import smartContentConfigStore from '../stores/smartContentConfigStore';
import SmartContent from '../SmartContent';

jest.mock('../stores/SmartContentStore', () => jest.fn(function() {
    this.items = [];
}));
jest.mock('../stores/smartContentConfigStore', () => ({
    getConfig: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
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
};

test('Pass correct sections prop', () => {
    smartContentConfigStore.getConfig.mockReturnValue({
        tags: true,
        categories: false,
        audienceTargeting: true,
        sorting: [],
        presentAs: false,
        limit: true,
    });

    const smartContentStore = new SmartContentStore('content');
    const smartContent = shallow(
        <SmartContent
            defaultValue={defaultValue}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(smartContent.find('FilterOverlay').prop('sections'))
        .toEqual(['tags', 'audienceTargeting', 'limit']);
});

test('Disable sorting on MultiItemSelection', () => {
    smartContentConfigStore.getConfig.mockReturnValue({
        tags: true,
        categories: false,
        audienceTargeting: true,
        sorting: [],
        presentAs: false,
        limit: true,
    });

    const smartContentStore = new SmartContentStore('content');
    const smartContent = shallow(
        <SmartContent
            defaultValue={defaultValue}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(smartContent.find('MultiItemSelection').prop('sortable')).toEqual(false);
});

test('Pass correct props to MultiItemSelection component', () => {
    smartContentConfigStore.getConfig.mockReturnValue({
        tags: true,
        categories: false,
        audienceTargeting: true,
        sorting: [],
        presentAs: false,
        limit: true,
    });

    const smartContentStore = new SmartContentStore('content');
    const smartContent = shallow(
        <SmartContent
            defaultValue={defaultValue}
            disabled={true}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(smartContent.find('MultiItemSelection').prop('disabled')).toEqual(true);
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
    });

    const presentations = [
        {name: 'one', value: 'One column'},
    ];

    const smartContentStore = new SmartContentStore('content');
    const smartContent = shallow(
        <SmartContent
            categoryRootKey="test1"
            defaultValue={defaultValue}
            fieldLabel="Test"
            presentations={presentations}
            store={smartContentStore}
        />
    );

    expect(smartContent.find('FilterOverlay').prop('categoryRootKey')).toEqual('test1');
    expect(smartContent.find('FilterOverlay').prop('dataSourceListKey')).toEqual('pages_list');
    expect(smartContent.find('FilterOverlay').prop('dataSourceResourceKey')).toEqual('pages');
    expect(smartContent.find('FilterOverlay').prop('sections'))
        .toEqual(['datasource', 'categories', 'sorting', 'presentation']);
    expect(smartContent.find('FilterOverlay').prop('presentations'))
        .toEqual({
            one: 'One column',
        });
});

test('Open and closes the FilterOverlay when the icon is clicked', () => {
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
    });
    const smartContent = shallow(
        <SmartContent
            defaultValue={defaultValue}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(smartContent.find('FilterOverlay').prop('open')).toEqual(false);

    smartContent.find('MultiItemSelection').prop('leftButton').onClick();
    expect(smartContent.find('FilterOverlay').prop('open')).toEqual(true);

    smartContent.find('FilterOverlay').prop('onClose')();
    expect(smartContent.find('FilterOverlay').prop('open')).toEqual(false);
    expect(smartContent.find('FilterOverlay').prop('title')).toEqual('sulu_admin.filter_overlay_title');
    expect(smartContent.find('FilterOverlay').prop('sortings')).toEqual([{name: 'title', value: 'Title'}]);
    expect(translate).toBeCalledWith('sulu_admin.filter_overlay_title', {fieldLabel: 'Test'});
});

test('Show items in a SmartContentItem', () => {
    const smartContentStore = new SmartContentStore('content');
    smartContentStore.items = [
        {title: 'Homepage'},
        {title: 'About us'},
    ];

    const smartContent = shallow(
        <SmartContent
            defaultValue={defaultValue}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(smartContent.find('SmartContentItem')).toHaveLength(2);
    expect(smartContent.find('SmartContentItem').at(0).prop('item')).toEqual({title: 'Homepage'});
    expect(smartContent.find('SmartContentItem').at(1).prop('item')).toEqual({title: 'About us'});
});

test('Pass the loading prop to the MultiItemSelection if items are still loading', () => {
    const smartContentStore = new SmartContentStore('content');
    smartContentStore.itemsLoading = true;

    const smartContent = shallow(
        <SmartContent
            defaultValue={defaultValue}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(smartContent.find('MultiItemSelection').prop('loading')).toEqual(true);
});

test('Pass the loading prop to the MultiItemSelection if list or categories are still loading', () => {
    const smartContentStore = new SmartContentStore('content');
    // $FlowFixMe
    smartContentStore.loading = true;

    const smartContent = shallow(
        <SmartContent
            defaultValue={defaultValue}
            fieldLabel="Test"
            store={smartContentStore}
        />
    );

    expect(smartContent.find('MultiItemSelection').prop('loading')).toEqual(true);
});
