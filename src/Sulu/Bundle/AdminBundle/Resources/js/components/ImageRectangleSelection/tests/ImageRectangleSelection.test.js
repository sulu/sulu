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
    const view = mount(<ImageRectangleSelection containerHeight={360} containerWidth={640} image="//:0" />);

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

    view.find('RectangleSelection').prop('onChange')(undefined);

    expect(changeSpy).toBeCalledWith(undefined);
});

test.each([
    [300, 600, 360, 640, 300, 600],
    [200, 200, 300, 400, 200, 200],
    [800, 800, 300, 400, 300, 300],
    [800, 1000, 200, 400, 200, 250],
    [1000, 800, 200, 400, 200, 160],
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
            />
        );

        const onImageLoad = view.instance().image.onload;
        view.instance().image = {
            naturalWidth: 1920,
            naturalHeight: 1080,
        };
        onImageLoad();

        view.update();

        const rectangle = view.find('RectangleSelection');
        expect(rectangle.length).toBe(1);
        expect(rectangle.props().minHeight).toEqual(expectedMinHeight);
        expect(rectangle.props().minWidth).toEqual(expectedMinWidth);
    }
);
