/* eslint-disable flowtype/require-valid-file-annotation */
import {mount} from 'enzyme';
import React from 'react';
import {ImageRectangleSelection} from '../ImageRectangleSelection';

jest.mock('../../../utils/DOM/afterElementsRendered');

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../withContainerSize/withContainerSize');

test('The component should render with image source', () => {
    const view = mount(
        <ImageRectangleSelection
            containerHeight={360}
            containerWidth={640}
            image="//:0"
            onChange={jest.fn()}
        />
    );

    const onImageLoad = view.instance().image.onload;
    view.instance().image = {
        naturalWidth: 1920,
        naturalHeight: 1080,
    };
    onImageLoad();

    view.update();

    expect(view.render()).toMatchSnapshot();
});

test('The component should calculate the selection with respect to the image', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <ImageRectangleSelection
            containerHeight={360}
            containerWidth={640}
            image="//:0"
            onChange={changeSpy}
            value={{height: 1080, left: 0, top: 0, width: 1920}}
        />
    );

    const onImageLoad = view.instance().image.onload;
    view.instance().image = {
        naturalWidth: 1920,
        naturalHeight: 1080,
    };
    onImageLoad();

    view.update();
    expect(view.render()).toMatchSnapshot();
});

test('The component should render with initial selection', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <ImageRectangleSelection
            containerHeight={360}
            containerWidth={640}
            image="//:0"
            onChange={changeSpy}
            value={{width: 1500, height: 800, top: 200, left: 300}}
        />
    );

    const onImageLoad = view.instance().image.onload;
    view.instance().image = {
        naturalWidth: 1920,
        naturalHeight: 1080,
    };
    onImageLoad();

    view.update();

    expect(view.render()).toMatchSnapshot();
});

test('The component should pass a value of undefined', () => {
    const changeSpy = jest.fn();

    const view = mount(
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

    const onImageLoad = view.instance().image.onload;
    view.instance().image = {
        naturalWidth: 1920,
        naturalHeight: 1080,
    };
    onImageLoad();
    view.update();

    view.find('RectangleSelectionRenderer').prop('onChange')(undefined);

    expect(changeSpy).toBeCalledWith(undefined);
});

test('The component should scale the value based on the image height and container height', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <ImageRectangleSelection
            containerHeight={360}
            containerWidth={640}
            image="//:0"
            onChange={changeSpy}
            value={undefined}
        />
    );

    const onImageLoad = view.instance().image.onload;
    view.instance().image = {
        naturalWidth: 1920,
        naturalHeight: 1080,
    };
    onImageLoad();
    view.update();

    view.find('RectangleSelectionRenderer').prop('onChange')({width: 320, height: 180, top: 0, left: 320});

    expect(changeSpy).toBeCalledWith({width: 960, height: 540, top: 0, left: 960});
});

test('The component should not scale the value to exceed the natural image width', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <ImageRectangleSelection
            // window.innerHeight = 798
            containerHeight={369}
            // window.innerWidth = 1440
            containerWidth={1000}
            image="//:0"
            onChange={changeSpy}
            value={undefined}
        />
    );

    const onImageLoad = view.instance().image.onload;
    view.instance().image = {
        naturalWidth: 4896,
        naturalHeight: 3264,
    };
    onImageLoad();
    view.update();

    view.find('RectangleSelectionRenderer').prop('onChange')({width: 554, height: 200, top: 0, left: 0});

    expect(changeSpy).toBeCalledWith({width: 4896, height: 1769.1056910569105, top: 0, left: 0});
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
        const view = mount(
            <ImageRectangleSelection
                containerHeight={containerHeight}
                containerWidth={containerWidth}
                image="//:0"
                minHeight={minHeight}
                minWidth={minWidth}
                onChange={jest.fn()}
            />
        );

        const onImageLoad = view.instance().image.onload;
        view.instance().image = {
            naturalWidth: 1920,
            naturalHeight: 1080,
        };
        onImageLoad();

        view.update();

        const rectangle = view.find('RectangleSelectionRenderer');
        expect(rectangle.length).toBe(1);
        expect(rectangle.props().minHeight).toEqual(expectedMinHeight);
        expect(rectangle.props().minWidth).toEqual(expectedMinWidth);
    }
);
