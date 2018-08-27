// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {translate} from '../../../utils/Translator';
import SmartContentStore from '../stores/SmartContentStore';
import smartContentConfigStore from '../stores/SmartContentConfigStore';
import SmartContent from '../SmartContent';

jest.mock('../stores/SmartContentStore', () => jest.fn());
jest.mock('../stores/SmartContentConfigStore', () => ({
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
        sorting: false,
        presentAs: false,
        limit: true,
    });

    const smartContentStore = new SmartContentStore();
    const smartContent = shallow(<SmartContent fieldLabel="Test" provider="content" store={smartContentStore} />);

    expect(smartContent.find('FilterOverlay').prop('sections'))
        .toEqual(['tags', 'audienceTargeting', 'limit']);
});

test('Pass correct sections prop with other values', () => {
    smartContentConfigStore.getConfig.mockReturnValue({
        datasourceResourceKey: 'pages',
        datasourceAdapter: 'table',
        tags: false,
        categories: true,
        audienceTargeting: false,
        sorting: true,
        presentAs: true,
        limit: false,
    });

    const smartContentStore = new SmartContentStore();
    const smartContent = shallow(<SmartContent fieldLabel="Test" provider="content" store={smartContentStore} />);

    expect(smartContent.find('FilterOverlay').prop('sections'))
        .toEqual(['datasource', 'categories', 'sorting', 'presentation']);
});

test('Open and closes the FilterOverlay when the icon is clicked', () => {
    const smartContentStore = new SmartContentStore();
    const smartContent = shallow(<SmartContent fieldLabel="Test" provider="content" store={smartContentStore} />);

    expect(smartContent.find('FilterOverlay').prop('open')).toEqual(false);

    smartContent.find('MultiItemSelection').prop('leftButton').onClick();
    expect(smartContent.find('FilterOverlay').prop('open')).toEqual(true);

    smartContent.find('FilterOverlay').prop('onClose')();
    expect(smartContent.find('FilterOverlay').prop('open')).toEqual(false);
    expect(smartContent.find('FilterOverlay').prop('title')).toEqual('sulu_admin.filter_overlay_title');
    expect(translate).toBeCalledWith('sulu_admin.filter_overlay_title', {fieldLabel: 'Test'});
});
