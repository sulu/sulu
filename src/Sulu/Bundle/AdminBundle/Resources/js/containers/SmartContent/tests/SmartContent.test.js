// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {translate} from '../../../utils/Translator';
import SmartContentStore from '../stores/SmartContentStore';
import SmartContent from '../SmartContent';

jest.mock('../stores/SmartContentStore', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Open and closes the FilterOverlay when the icon is clicked', () => {
    const smartContentStore = new SmartContentStore();
    const smartContent = shallow(<SmartContent fieldLabel="Test" store={smartContentStore} />);

    expect(smartContent.find('FilterOverlay').prop('open')).toEqual(false);

    smartContent.find('MultiItemSelection').prop('leftButton').onClick();
    expect(smartContent.find('FilterOverlay').prop('open')).toEqual(true);

    smartContent.find('FilterOverlay').prop('onClose')();
    expect(smartContent.find('FilterOverlay').prop('open')).toEqual(false);
    expect(smartContent.find('FilterOverlay').prop('title')).toEqual('sulu_admin.filter_overlay_title');
    expect(translate).toBeCalledWith('sulu_admin.filter_overlay_title', {fieldLabel: 'Test'});
});
