// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import ImageFocusPoint from '../ImageFocusPoint';

function renderImageFocusPoint(value, onChange = jest.fn()) {
    const view = render(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={onChange}
            value={value}
        />
    );

    return {
        ...view,
        onChange,
    };
}

function getImage(container) {
    const image = container.querySelector('img');

    if (!image) {
        throw new Error('Expected image element');
    }

    return image;
}

function loadImageWithSize(container, width, height) {
    const image = getImage(container);

    jest.spyOn(image, 'getBoundingClientRect').mockReturnValue({
        bottom: 0,
        height,
        left: 0,
        right: 0,
        top: 0,
        width,
        x: 0,
        y: 0,
        toJSON: jest.fn(),
    });
    fireEvent.load(image);
}

function getFocusPoints(container) {
    const focusPoints = container.querySelector('.focusPoints');

    if (!focusPoints) {
        throw new Error('Expected focus points container');
    }

    return focusPoints;
}

test('Should render Loader at the beginning', () => {
    const {asFragment} = renderImageFocusPoint({x: 0, y: 0});

    expect(asFragment()).toMatchSnapshot();
});

test('Should render focus point cells with correct size', () => {
    const {container} = renderImageFocusPoint({x: 0, y: 0});

    loadImageWithSize(container, 50, 50);
    expect(getFocusPoints(container).style).toEqual(expect.objectContaining({height: '50px', width: '50px'}));

    loadImageWithSize(container, 200, 200);
    window.dispatchEvent(new Event('resize'));
    expect(getFocusPoints(container).style).toEqual(expect.objectContaining({height: '200px', width: '200px'}));
});

test('Should render ImageFocusPoint with focusing the top-left point', () => {
    const {asFragment, container} = renderImageFocusPoint({x: 0, y: 0});

    loadImageWithSize(container, 300, 300);
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the top-center point', () => {
    const {asFragment, container} = renderImageFocusPoint({x: 1, y: 0});

    loadImageWithSize(container, 300, 300);
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the top-right point', () => {
    const {asFragment, container} = renderImageFocusPoint({x: 2, y: 0});

    loadImageWithSize(container, 300, 300);
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the center-left point', () => {
    const {asFragment, container} = renderImageFocusPoint({x: 0, y: 1});

    loadImageWithSize(container, 300, 300);
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the center-center point', () => {
    const {asFragment, container} = renderImageFocusPoint({x: 1, y: 1});

    loadImageWithSize(container, 300, 300);
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the center-right point', () => {
    const {asFragment, container} = renderImageFocusPoint({x: 2, y: 1});

    loadImageWithSize(container, 300, 300);
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the bottom-left point', () => {
    const {asFragment, container} = renderImageFocusPoint({x: 0, y: 2});

    loadImageWithSize(container, 300, 300);
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the bottom-center point', () => {
    const {asFragment, container} = renderImageFocusPoint({x: 1, y: 2});

    loadImageWithSize(container, 300, 300);
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the bottom-right point', () => {
    const {asFragment, container} = renderImageFocusPoint({x: 2, y: 2});

    loadImageWithSize(container, 300, 300);
    expect(asFragment()).toMatchSnapshot();
});

test('Should call the onClick handler when a focus point was clicked', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const {container} = renderImageFocusPoint({x: 1, y: 1}, changeSpy);

    loadImageWithSize(container, 300, 300);
    const buttons = screen.getAllByRole('button');

    await user.click(buttons[0]);
    expect(changeSpy).toHaveBeenCalledWith({x: 0, y: 0});

    await user.click(buttons[1]);
    expect(changeSpy).toHaveBeenCalledWith({x: 1, y: 0});

    await user.click(buttons[3]);
    expect(changeSpy).toHaveBeenCalledWith({x: 0, y: 1});
});

test('Should disable the selected focus point button', () => {
    const {container} = renderImageFocusPoint({x: 0, y: 0});

    loadImageWithSize(container, 300, 300);
    const buttons = screen.getAllByRole('button');

    expect(buttons[0]).toBeDisabled();
});
