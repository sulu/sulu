// @flow
import React from 'react';
import {shallow} from 'enzyme';
import PublishIndicator from '../PublishIndicator';

test('Show only the publish icon', () => {
    const publishIndicator = shallow(<PublishIndicator published={true} />);

    expect(publishIndicator.find('.published')).toHaveLength(1);
    expect(publishIndicator.find('.draft')).toHaveLength(0);
});

test('Show only the draft icon', () => {
    const publishIndicator = shallow(<PublishIndicator draft={true} />);

    expect(publishIndicator.find('.draft')).toHaveLength(1);
});

test('Show the draft and published icon', () => {
    const publishIndicator = shallow(<PublishIndicator draft={true} published={true} />);

    expect(publishIndicator.find('.draft')).toHaveLength(1);
    expect(publishIndicator.find('.published')).toHaveLength(1);
});
