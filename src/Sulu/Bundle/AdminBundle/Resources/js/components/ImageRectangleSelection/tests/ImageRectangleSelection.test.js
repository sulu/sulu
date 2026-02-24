/* global global */
/* eslint-disable flowtype/require-valid-file-annotation, flowtype/no-types-missing-file-annotation */
import {act, render} from '@testing-library/react';
import React from 'react';
import RectangleSelection from '../../RectangleSelection';
import {ImageRectangleSelection} from '../ImageRectangleSelection';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../RectangleSelection', () => jest.fn((props) => (
    <div data-testid="rectangle-selection">
        {props.children}
    </div>
)));

const imageInstances = [];
const OriginalImage = global.Image;

class MockImage {
    naturalHeight: number;
    naturalWidth: number;
    onerror: () => void;
    onload: () => void;
    src: string;

    constructor() {
        imageInstances.push(this);
        this.naturalHeight = 0;
        this.naturalWidth = 0;
        this.src = '';
    }
}

beforeEach(() => {
    imageInstances.length = 0;
    jest.clearAllMocks();
    // $FlowFixMe[prop-missing]
    global.Image = MockImage;
});

afterEach(() => {
    // $FlowFixMe[prop-missing]
    global.Image = OriginalImage;
});

function renderImageRectangleSelection(props = {}) {
    return render(
        <ImageRectangleSelection
            containerHeight={360}
            containerWidth={640}
            image="//:0"
            onChange={jest.fn()}
            {...props}
        />
    );
}

function loadImage(index = 0, naturalWidth = 1920, naturalHeight = 1080) {
    imageInstances[index].naturalWidth = naturalWidth;
    imageInstances[index].naturalHeight = naturalHeight;
    act(() => {
        imageInstances[index].onload();
    });
}

function getLatestRectangleSelectionProps() {
    return RectangleSelection.mock.calls[RectangleSelection.mock.calls.length - 1][0];
}

test('The component should render with image source', () => {
    const {asFragment} = renderImageRectangleSelection();

    loadImage();
    expect(asFragment()).toMatchSnapshot();
});

test('The component should calculate the selection with respect to the image', () => {
    const changeSpy = jest.fn();
    const {asFragment} = renderImageRectangleSelection({
        onChange: changeSpy,
        value: {height: 1080, left: 0, top: 0, width: 1920},
    });

    loadImage();
    expect(asFragment()).toMatchSnapshot();
});

test('The component should render with initial selection', () => {
    const changeSpy = jest.fn();
    const {asFragment} = renderImageRectangleSelection({
        onChange: changeSpy,
        value: {width: 1500, height: 800, top: 200, left: 300},
    });

    loadImage();
    expect(asFragment()).toMatchSnapshot();
});

test('The component should pass a value of undefined', () => {
    const changeSpy = jest.fn();
    renderImageRectangleSelection({
        minHeight: 300,
        minWidth: 600,
        onChange: changeSpy,
        value: {width: 1500, height: 800, top: 200, left: 300},
    });

    loadImage();
    act(() => {
        getLatestRectangleSelectionProps().onChange(undefined);
    });

    expect(changeSpy).toBeCalledWith(undefined);
});

test('The component should scale the value based on the image height and container height', () => {
    const changeSpy = jest.fn();
    renderImageRectangleSelection({
        onChange: changeSpy,
        value: undefined,
    });

    loadImage();
    act(() => {
        getLatestRectangleSelectionProps().onChange({width: 320, height: 180, top: 0, left: 320});
    });

    expect(changeSpy).toBeCalledWith({width: 960, height: 540, top: 0, left: 960});
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

    loadImage(0, 4896, 3264);
    act(() => {
        getLatestRectangleSelectionProps().onChange({width: 554, height: 200, top: 0, left: 0});
    });

    expect(changeSpy).toBeCalledWith({width: 4896, height: 1769.1056910569105, top: 0, left: 0});
});

test('The component should keep converted bottom edge inside natural image bounds', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <ImageRectangleSelection
            containerHeight={200}
            containerWidth={200}
            image="//:0"
            onChange={changeSpy}
            value={undefined}
        />
    );

    const onImageLoad = view.instance().image.onload;
    view.instance().image = {
        naturalWidth: 253,
        naturalHeight: 200,
    };
    onImageLoad();
    view.update();

    // Simulate a measured selection where the bottom edge is slightly below the actual scaled image.
    view.find('RectangleSelectionComponent').prop('onChange')({width: 200, height: 100, top: 59, left: 0});

    expect(changeSpy).toBeCalledWith(expect.objectContaining({
        left: 0,
        width: 253,
    }));

    const convertedSelection = changeSpy.mock.calls[changeSpy.mock.calls.length - 1][0];
    expect(convertedSelection.top + convertedSelection.height).toBeLessThanOrEqual(200);
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
            />
        );

        loadImage();

        expect(getLatestRectangleSelectionProps().minHeight).toEqual(expectedMinHeight);
        expect(getLatestRectangleSelectionProps().minWidth).toEqual(expectedMinWidth);
    }
);
