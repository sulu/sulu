// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {ImageRectangleSelection} from '../ImageRectangleSelection';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../utils/DOM/afterElementsRendered');

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../withContainerSize/withContainerSize');

jest.mock('../../RectangleSelection', () => {
    const React = require('react');

    return jest.fn(function RectangleSelectionMock({children}) {
        return React.createElement('div', {'data-testid': 'rectangle-selection'}, children);
    });
});

const rectangleSelection = (jest.requireMock('../../RectangleSelection'): any);

let OriginalImage: any;
let lastImageInstance: any;

beforeEach(() => {
    jest.clearAllMocks();
    lastImageInstance = null;
    OriginalImage = window.Image;
    window.Image = class MockImage {
        onerror: ?() => void;
        onload: ?() => void;
        src: ?string;
        naturalWidth: number;
        naturalHeight: number;

        constructor() {
            this.onerror = undefined;
            this.onload = undefined;
            this.src = undefined;
            this.naturalWidth = 0;
            this.naturalHeight = 0;
            lastImageInstance = (this: any);
        }
    };
});

afterEach(() => {
    window.Image = OriginalImage;
});

function triggerImageLoad(naturalWidth: number, naturalHeight: number) {
    if (!lastImageInstance) {
        throw new Error('Image instance was not created');
    }

    lastImageInstance.naturalWidth = naturalWidth;
    lastImageInstance.naturalHeight = naturalHeight;

    act(() => {
        if (lastImageInstance.onload) {
            lastImageInstance.onload();
        }
    });
}

test('The component should render with image source', () => {
    const view = render(
        <ImageRectangleSelection
            containerHeight={360}
            containerWidth={640}
            image="//:0"
            onChange={jest.fn()}
            value={undefined}
        />
    );

    triggerImageLoad(1920, 1080);

    expect(view.asFragment()).toMatchSnapshot();
});

test('The component should calculate the selection with respect to the image', () => {
    const view = render(
        <ImageRectangleSelection
            containerHeight={360}
            containerWidth={640}
            image="//:0"
            onChange={jest.fn()}
            value={{height: 1080, left: 0, top: 0, width: 1920}}
        />
    );

    triggerImageLoad(1920, 1080);

    expect(view.asFragment()).toMatchSnapshot();
});

test('The component should render with initial selection', () => {
    const view = render(
        <ImageRectangleSelection
            containerHeight={360}
            containerWidth={640}
            image="//:0"
            onChange={jest.fn()}
            value={{width: 1500, height: 800, top: 200, left: 300}}
        />
    );

    triggerImageLoad(1920, 1080);

    expect(view.asFragment()).toMatchSnapshot();
});

test('The component should pass a value of undefined', () => {
    const changeSpy = jest.fn();

    render(
        <ImageRectangleSelection
            containerHeight={360}
            containerWidth={640}
            image="//:0"
            minHeight={300}
            minWidth={600}
            onChange={changeSpy}
            value={{width: 1500, height: 800, top: 200, left: 300}}
        />
    );

    triggerImageLoad(1920, 1080);
    getLatestMockProps(rectangleSelection).onChange(undefined);

    expect(changeSpy).toHaveBeenCalledWith(undefined);
});

test('The component should scale the value based on the image height and container height', () => {
    const changeSpy = jest.fn();

    render(
        <ImageRectangleSelection
            containerHeight={360}
            containerWidth={640}
            image="//:0"
            onChange={changeSpy}
            value={undefined}
        />
    );

    triggerImageLoad(1920, 1080);
    getLatestMockProps(rectangleSelection).onChange({width: 320, height: 180, top: 0, left: 320});

    expect(changeSpy).toHaveBeenCalledWith({width: 960, height: 540, top: 0, left: 960});
});

test('The component should not scale the value to exceed the natural image width', () => {
    const changeSpy = jest.fn();

    render(
        <ImageRectangleSelection
            containerHeight={369}
            containerWidth={1000}
            image="//:0"
            onChange={changeSpy}
            value={undefined}
        />
    );

    triggerImageLoad(4896, 3264);
    getLatestMockProps(rectangleSelection).onChange({width: 554, height: 200, top: 0, left: 0});

    expect(changeSpy).toHaveBeenCalledWith({width: 4896, height: 1769.1056910569105, top: 0, left: 0});
});

test.each([
    [300, 600, 360, 640, 100, 200],
    [200, 200, 400, 480, 50, 50],
    [600, 300, 180, 480, 100, 50],
    [800, 1000, 400, 240, 100, 125],
    [1000, 800, 400, 240, 125, 100],
    [500, 500, 1600, 2000, 500, 500],
])(
    'The component should render with minHeight %s, minWidth %s, containerHeight %s and containerWidth %s',
    (minHeight, minWidth, containerHeight, containerWidth, expectedMinHeight, expectedMinWidth) => {
        render(
            <ImageRectangleSelection
                containerHeight={containerHeight}
                containerWidth={containerWidth}
                image="//:0"
                minHeight={minHeight}
                minWidth={minWidth}
                onChange={jest.fn()}
                value={undefined}
            />
        );

        triggerImageLoad(1920, 1080);

        const rectangle = getLatestMockProps(rectangleSelection);
        expect(rectangle.minHeight).toEqual(expectedMinHeight);
        expect(rectangle.minWidth).toEqual(expectedMinWidth);
    }
);
