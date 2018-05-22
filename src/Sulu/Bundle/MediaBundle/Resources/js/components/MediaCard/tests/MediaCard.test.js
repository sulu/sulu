/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import pretty from 'pretty';
import React from 'react';
import MediaCard from '../MediaCard';

test('Render a MediaCard component', () => {
    expect(render(
        <MediaCard
            image="http://lorempixel.com/300/200"
            meta="Test/Test"
            title="Test"
        />
    )).toMatchSnapshot();
});

test('Render a MediaCard component with a checkbox for selection', () => {
    expect(render(
        <MediaCard
            image="http://lorempixel.com/300/200"
            meta="Test/Test"
            onSelectionChange={jest.fn()}
            title="Test"
        />
    )).toMatchSnapshot();
});

test('Render a MediaCard with download list', () => {
    const imageSizes = [
        {
            url: 'http://lorempixel.com/300/200',
            label: '300/200',
        },
        {
            url: 'http://lorempixel.com/600/300',
            label: '600/300',
        },
        {
            url: 'http://lorempixel.com/150/200',
            label: '150/200',
        },
    ];

    const masonry = mount(
        <MediaCard
            downloadCopyText="Copy URL"
            downloadText="Direct download"
            downloadUrl="http://lorempixel.com/300/200"
            image="http://lorempixel.com/300/200"
            imageSizes={imageSizes}
            meta="Test/Test"
            title="Test"
        />
    );

    masonry.instance().openDownloadList();
    expect(pretty(document.body.innerHTML)).toMatchSnapshot();
});

test('Clicking on an item should call the responsible handler on the MediaCard component', () => {
    const clickSpy = jest.fn();
    const selectionSpy = jest.fn();
    const itemId = 'test';

    const mediaCard = mount(
        <MediaCard
            id={itemId}
            image="http://lorempixel.com/300/200"
            meta="Test/Test"
            onClick={clickSpy}
            onSelectionChange={selectionSpy}
            title="Test"
        />
    );

    mediaCard.find('MediaCard .media').simulate('click');
    expect(clickSpy).toHaveBeenCalledWith(itemId, true);

    mediaCard.find('MediaCard .description').simulate('click');
    expect(selectionSpy).toHaveBeenCalledWith(itemId, true);
});
