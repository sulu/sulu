/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import MediaCard from '../MediaCard';

test('Render a simple MediaCard component', () => {
    expect(render(
        <MediaCard
            title="Test"
            meta="Test/Test"
        >
            <img src="http://lorempixel.com/300/200" />
        </MediaCard>
    )).toMatchSnapshot();
});

test('Clicking on an item should call the responsible handler on the MediaCard component', () => {
    const clickSpy = jest.fn();
    const selectionSpy = jest.fn();
    const itemId = 'test';

    const masonry = mount(
        <MediaCard
            id={itemId}
            title="Test"
            meta="Test/Test"
            onClick={clickSpy}
            onSelectionChange={selectionSpy}
        >
            <img src="http://lorempixel.com/300/200" />
        </MediaCard>
    );

    masonry.find('MediaCard .media').simulate('click');
    expect(clickSpy).toHaveBeenCalledWith(itemId);

    masonry.find('MediaCard .headerClickArea').simulate('click');
    expect(selectionSpy).toHaveBeenCalledWith(itemId, true);
});
