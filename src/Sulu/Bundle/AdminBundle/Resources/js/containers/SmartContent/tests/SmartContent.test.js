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
    const smartContent = shallow(<SmartContent fieldLabel="Test" store={smartContentStore} />);

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
    const smartContent = shallow(<SmartContent fieldLabel="Test" store={smartContentStore} />);

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
    const smartContent = shallow(<SmartContent disabled={true} fieldLabel="Test" store={smartContentStore} />);

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
        <SmartContent fieldLabel="Test" presentations={presentations} store={smartContentStore} />
    );

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
        sorting: [{name: 'title', value: 'Title'}],
        presentAs: true,
        limit: false,
    });
    const smartContent = shallow(<SmartContent fieldLabel="Test" store={smartContentStore} />);

    expect(smartContent.find('FilterOverlay').prop('open')).toEqual(false);

    smartContent.find('MultiItemSelection').prop('leftButton').onClick();
    expect(smartContent.find('FilterOverlay').prop('open')).toEqual(true);

    smartContent.find('FilterOverlay').prop('onClose')();
    expect(smartContent.find('FilterOverlay').prop('open')).toEqual(false);
    expect(smartContent.find('FilterOverlay').prop('title')).toEqual('sulu_admin.filter_overlay_title');
    expect(smartContent.find('FilterOverlay').prop('sortings')).toEqual({title: 'Title'});
    expect(translate).toBeCalledWith('sulu_admin.filter_overlay_title', {fieldLabel: 'Test'});
});

test('Show items in a SmartContentItem', () => {
    const smartContentStore = new SmartContentStore('content');
    smartContentStore.items = [
        {title: 'Homepage'},
        {title: 'About us'},
    ];

    const smartContent = shallow(<SmartContent fieldLabel="Test" store={smartContentStore} />);

    expect(smartContent.find('SmartContentItem')).toHaveLength(2);
    expect(smartContent.find('SmartContentItem').at(0).prop('item')).toEqual({title: 'Homepage'});
    expect(smartContent.find('SmartContentItem').at(1).prop('item')).toEqual({title: 'About us'});
});

test('Pass the loading prop to the MultiItemSelection if items are still loading', () => {
    const smartContentStore = new SmartContentStore('content');
    smartContentStore.itemsLoading = true;

    const smartContent = shallow(<SmartContent fieldLabel="Test" store={smartContentStore} />);

    expect(smartContent.find('MultiItemSelection').prop('loading')).toEqual(true);
});

test('Pass the loading prop to the MultiItemSelection if list or categories are still loading', () => {
    const smartContentStore = new SmartContentStore('content');
    // $FlowFixMe
    smartContentStore.loading = true;

    const smartContent = shallow(<SmartContent fieldLabel="Test" store={smartContentStore} />);

    expect(smartContent.find('MultiItemSelection').prop('loading')).toEqual(true);
});

test('Set all defaults on the SmartContentStore', () => {
    smartContentConfigStore.getConfig.mockReturnValue({
        datasourceResourceKey: 'pages',
        datasourceAdapter: 'table',
        tags: true,
        categories: true,
        audienceTargeting: true,
        sorting: [{name: 'title', value: 'Title'}],
        presentAs: true,
        limit: true,
    });

    const smartContentStore = new SmartContentStore('content');
    shallow(<SmartContent fieldLabel="Test" store={smartContentStore} />);

    expect(smartContentStore.dataSource).toEqual(undefined);
    expect(smartContentStore.includeSubElements).toEqual(false);
    expect(smartContentStore.categories).toEqual(undefined);
    expect(smartContentStore.categoryOperator).toEqual('or');
    expect(smartContentStore.tags).toEqual(undefined);
    expect(smartContentStore.tagOperator).toEqual('or');
    expect(smartContentStore.audienceTargeting).toEqual(false);
    expect(smartContentStore.sortBy).toEqual('title');
    expect(smartContentStore.sortOrder).toEqual('asc');
    expect(smartContentStore.presentation).toEqual(undefined);
    expect(smartContentStore.limit).toEqual(undefined);
});

test('Set no defaults on the SmartContentStore', () => {
    smartContentConfigStore.getConfig.mockReturnValue({
        datasourceResourceKey: undefined,
        datasourceAdapter: undefined,
        tags: false,
        categories: false,
        audienceTargeting: false,
        sorting: [],
        presentAs: false,
        limit: false,
    });

    const smartContentStore = new SmartContentStore('content');
    shallow(<SmartContent fieldLabel="Test" store={smartContentStore} />);

    expect(smartContentStore.dataSource).toEqual(undefined);
    expect(smartContentStore.includeSubElements).toEqual(undefined);
    expect(smartContentStore.categories).toEqual(undefined);
    expect(smartContentStore.categoryOperator).toEqual(undefined);
    expect(smartContentStore.tags).toEqual(undefined);
    expect(smartContentStore.tagOperator).toEqual(undefined);
    expect(smartContentStore.audienceTargeting).toEqual(undefined);
    expect(smartContentStore.sortBy).toEqual(undefined);
    expect(smartContentStore.sortOrder).toEqual(undefined);
    expect(smartContentStore.presentation).toEqual(undefined);
    expect(smartContentStore.limit).toEqual(undefined);
});
