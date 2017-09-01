/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import MasonryMediaItem from '../MasonryMediaItem';

test('Render a simple MasonryMediaItem component', () => {
    expect(render(
        <MasonryMediaItem
            mediaTitle="Test"
            metaInfo="Test/Test">
            <img src="http://lorempixel.com/300/200" />
        </MasonryMediaItem>
    )).toMatchSnapshot();
});

test('Clicking on an item should call the responsible handler on the MasonryMediaItem component', () => {
    const clickSpy = jest.fn();
    const selectionSpy = jest.fn();
    const itemId = 'test';

    const masonry = mount(
        <MasonryMediaItem
            id={itemId}
            mediaTitle="Test"
            metaInfo="Test/Test"
            onClick={clickSpy}
            onSelectionChange={selectionSpy}>
            <img src="http://lorempixel.com/300/200" />
        </MasonryMediaItem>
    );

    masonry.find('MasonryMediaItem .media').simulate('click');
    expect(clickSpy).toHaveBeenCalledWith(itemId);

    masonry.find('MasonryMediaItem .headerClickArea').simulate('click');
    expect(selectionSpy).toHaveBeenCalledWith(itemId, true);
});
