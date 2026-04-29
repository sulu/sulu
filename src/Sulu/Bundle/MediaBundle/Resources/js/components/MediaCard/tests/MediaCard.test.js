/* global global */
// @flow
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import MediaCard from '../MediaCard';

const IMAGE_URL = 'http://lorempixel.com/300/200';
const imageInstances: Array<MockImage> = [];
const OriginalImage = global.Image;

class MockImage {
    _src: string;
    onerror: () => void;
    onload: () => void;

    constructor() {
        imageInstances.push(this);
    }

    set src(src: string) {
        this._src = src;
    }

    get src() {
        return this._src;
    }
}

beforeEach(() => {
    imageInstances.length = 0;
    // $FlowFixMe[prop-missing]
    global.Image = MockImage;
});

afterEach(() => {
    // $FlowFixMe[prop-missing]
    global.Image = OriginalImage;
});

function renderMediaCard(props: any = {}) {
    return render(
        <MediaCard
            downloadText=""
            downloadUrl=""
            id="test"
            image={IMAGE_URL}
            meta="Test/Test"
            mimeType="image/jpeg"
            title="Test"
            {...props}
        />
    );
}

function triggerImageLoad(index = 0) {
    act(() => {
        imageInstances[index].onload();
    });
}

function triggerImageError(index = 0) {
    act(() => {
        imageInstances[index].onerror();
    });
}

function getRequiredElement(container, selector) {
    const element = container.querySelector(selector);

    if (!element) {
        throw new Error(`Expected element for selector "${selector}"`);
    }

    return element;
}

test('Render a MediaCard component', () => {
    const {asFragment} = renderMediaCard();

    triggerImageLoad();
    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaCard component with ghostLocale', () => {
    const {asFragment} = renderMediaCard({ghostLocale: 'en'});

    triggerImageLoad();
    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaCard component with loader if image has not been loaded yet', () => {
    const {asFragment} = renderMediaCard();

    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaCard component with MimeTypeIndicator if an error appeared while loading the image', () => {
    const {asFragment} = renderMediaCard();

    triggerImageError();
    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaCard component with a checkbox for selection', () => {
    const {asFragment} = renderMediaCard({onSelectionChange: jest.fn()});

    triggerImageLoad();
    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaCard with download list', async() => {
    const user = userEvent.setup();
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

    renderMediaCard({
        downloadCopyText: 'Copy URL',
        downloadText: 'Direct download',
        downloadUrl: 'http://lorempixel.com/300/200',
        imageSizes,
    });

    await user.click(screen.getByRole('button', {name: 'su-download'}));

    expect(document.body).toMatchSnapshot();
});

test('Clicking on an item should call the responsible handler on the MediaCard component', async() => {
    const user = userEvent.setup();
    const clickSpy = jest.fn();
    const selectionSpy = jest.fn();
    const itemId = 'test';

    const {container} = renderMediaCard({
        id: itemId,
        onClick: clickSpy,
        onSelectionChange: selectionSpy,
    });

    await user.click(getRequiredElement(container, '.media'));
    expect(clickSpy).toHaveBeenCalledWith(itemId, true);

    await user.click(getRequiredElement(container, '.description'));
    expect(selectionSpy).toHaveBeenCalledWith(itemId, true);
});
