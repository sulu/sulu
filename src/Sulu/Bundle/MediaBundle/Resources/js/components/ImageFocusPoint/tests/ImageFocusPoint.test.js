// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ImageFocusPoint from '../ImageFocusPoint';

function triggerLoadWithDimensions(width: string, height: string) {
    const image = screen.getByRole('img');
    const getBoundingClientRectMock = jest.fn(() => ({width, height}));
    image.getBoundingClientRect = getBoundingClientRectMock;

    act(() => {
        image.dispatchEvent(new Event('load'));
    });

    return getBoundingClientRectMock;
}

function renderLoadedImageFocusPoint(value: {x: number, y: number}) {
    const view = render(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={value}
        />
    );

    triggerLoadWithDimensions('300px', '300px');

    return view;
}

test('Should render Loader at the beginning', () => {
    const {asFragment} = render(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={{x: 0, y: 0}}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render focus point cells with correct size', () => {
    render(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={{x: 0, y: 0}}
        />
    );

    const getBoundingClientRectMock = triggerLoadWithDimensions('50px', '50px');
    expect(screen.getAllByRole('button')[0].parentElement).toHaveStyle({height: '50px', width: '50px'});

    getBoundingClientRectMock.mockReturnValueOnce({width: '200px', height: '200px'});
    act(() => {
        window.dispatchEvent(new Event('resize'));
    });

    expect(screen.getAllByRole('button')[0].parentElement).toHaveStyle({height: '200px', width: '200px'});
});

test('Should render ImageFocusPoint with focusing the top-left point', () => {
    const {asFragment} = renderLoadedImageFocusPoint({x: 0, y: 0});
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the top-center point', () => {
    const {asFragment} = renderLoadedImageFocusPoint({x: 1, y: 0});
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the top-right point', () => {
    const {asFragment} = renderLoadedImageFocusPoint({x: 2, y: 0});
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the center-left point', () => {
    const {asFragment} = renderLoadedImageFocusPoint({x: 0, y: 1});
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the center-center point', () => {
    const {asFragment} = renderLoadedImageFocusPoint({x: 1, y: 1});
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the center-right point', () => {
    const {asFragment} = renderLoadedImageFocusPoint({x: 2, y: 1});
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the bottom-left point', () => {
    const {asFragment} = renderLoadedImageFocusPoint({x: 0, y: 2});
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the bottom-center point', () => {
    const {asFragment} = renderLoadedImageFocusPoint({x: 1, y: 2});
    expect(asFragment()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the bottom-right point', () => {
    const {asFragment} = renderLoadedImageFocusPoint({x: 2, y: 2});
    expect(asFragment()).toMatchSnapshot();
});

test('Should call the onClick handler when a focus point was clicked', async() => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={changeSpy}
            value={{x: 1, y: 1}}
        />
    );
    triggerLoadWithDimensions('300px', '300px');

    const buttons = screen.getAllByRole('button');
    await user.click(buttons[0]);
    expect(changeSpy).toHaveBeenCalledWith({x: 0, y: 0});

    await user.click(buttons[1]);
    expect(changeSpy).toHaveBeenCalledWith({x: 1, y: 0});

    await user.click(buttons[3]);
    expect(changeSpy).toHaveBeenCalledWith({x: 0, y: 1});
});

test('Should disable the selected focus point button', () => {
    render(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={{x: 0, y: 0}}
        />
    );
    triggerLoadWithDimensions('300px', '300px');

    expect(screen.getAllByRole('button')[0]).toBeDisabled();
});
