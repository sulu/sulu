/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import Masonry from '../Masonry';

test('Render an empty Masonry container', () => {
    expect(render(<Masonry />)).toMatchSnapshot();
});

test('Render a Masonry container with one item', () => {
    expect(render(
        <Masonry>
            <div>
                <img src="http://lorempixel.com/300/200" />
            </div>
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
            onItemSelectionChange={selectionSpy}
        >
            <div id={itemId}>
                <img src="http://lorempixel.com/300/200" />
            </div>
        </Masonry>
    );

    masonry.find('div .media').simulate('click');
    expect(clickSpy).toHaveBeenCalledWith(itemId);

    masonry.find('div .headerClickArea').simulate('click');
    expect(selectionSpy).toHaveBeenCalledWith(itemId, true);
});
