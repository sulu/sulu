/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import Masonry from '../Masonry';
import MasonryMediaItem from '../../MasonryMediaItem';

test('Render an empty Masonry container', () => {
    expect(render(
        <Masonry></Masonry>
    )).toMatchSnapshot();
});

test('Render a Masonry container with one item', () => {
    expect(render(
        <Masonry>
            <MasonryMediaItem>
                <img src="http://lorempixel.com/300/200" />
            </MasonryMediaItem>
        </Masonry>
    )).toMatchSnapshot();
});

test('Clicking on an item should call the responsible handler on the Masonry component', () => {
    const clickSpy = jest.fn();
    const selectionSpy = jest.fn();
    const itemId = 'test';

    const masonry = mount(
        <Masonry
            onItemClick={clickSpy}
            onItemSelectionChange={selectionSpy}>
            <MasonryMediaItem id={itemId}>
                <img src="http://lorempixel.com/300/200" />
            </MasonryMediaItem>
        </Masonry>
    );

    masonry.find('MasonryMediaItem .media').simulate('click');
    expect(clickSpy).toHaveBeenCalledWith(itemId);

    masonry.find('MasonryMediaItem .headerClickArea').simulate('click');
    expect(selectionSpy).toHaveBeenCalledWith(itemId, true);
});
