// @flow
import {mount} from 'enzyme';
import React from 'react';
import MediaCard from '../MediaCard';

test('Render a MediaCard component', () => {
    const mediaCard = mount(
        <MediaCard
            downloadText=""
            downloadUrl=""
            id="test"
            image="http://lorempixel.com/300/200"
            meta="Test/Test"
            mimeType="image/jpeg"
            title="Test"
        />
    );

    mediaCard.instance().image.onload();

    expect(mediaCard.render()).toMatchSnapshot();
});

test('Render a MediaCard component with loader if image has not been loaded yet', () => {
    const mediaCard = mount(
        <MediaCard
            downloadText=""
            downloadUrl=""
            id="test"
            image="http://lorempixel.com/300/200"
            meta="Test/Test"
            mimeType="image/jpeg"
            title="Test"
        />
    );

    expect(mediaCard.render()).toMatchSnapshot();
});

test('Render a MediaCard component with a checkbox for selection', () => {
    const mediaCard = mount(
        <MediaCard
            downloadText=""
            downloadUrl=""
            id="test"
            image="http://lorempixel.com/300/200"
            meta="Test/Test"
            mimeType="image/jpeg"
            onSelectionChange={jest.fn()}
            title="Test"
        />
    );

    mediaCard.instance().image.onload();

    expect(mediaCard.render()).toMatchSnapshot();
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

    const mediaCard = mount(
        <MediaCard
            downloadCopyText="Copy URL"
            downloadText="Direct download"
            downloadUrl="http://lorempixel.com/300/200"
            id="test"
            image="http://lorempixel.com/300/200"
            imageSizes={imageSizes}
            meta="Test/Test"
            mimeType="image/jpeg"
            title="Test"
        />
    );

    mediaCard.instance().openDownloadList();
    mediaCard.update();
    expect(mediaCard.find('DownloadList Popover').render()).toMatchSnapshot();
});

test('Clicking on an item should call the responsible handler on the MediaCard component', () => {
    const clickSpy = jest.fn();
    const selectionSpy = jest.fn();
    const itemId = 'test';

    const mediaCard = mount(
        <MediaCard
            downloadText=""
            downloadUrl=""
            id={itemId}
            image="http://lorempixel.com/300/200"
            meta="Test/Test"
            mimeType="image/jpeg"
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
