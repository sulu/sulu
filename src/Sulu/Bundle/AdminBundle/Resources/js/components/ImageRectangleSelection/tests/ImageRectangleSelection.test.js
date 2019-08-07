/* eslint-disable flowtype/require-valid-file-annotation */
import {mount} from 'enzyme';
import React from 'react';
import {ImageRectangleSelection} from '../ImageRectangleSelection';

jest.mock('../../../utils/DOM/afterElementsRendered');

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

test('The component should render with minWidth and minHeight', () => {
    const view = mount(
        <ImageRectangleSelection
            containerHeight={360}
            containerWidth={640}
            image="//:0"
            minHeight={300}
            minWidth={600}
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
    expect(rectangle.props().minWidth).toBe(200);
    expect(rectangle.props().minHeight).toBe(100);
});
