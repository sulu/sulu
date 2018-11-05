// @flow
import {mount} from 'enzyme';
import React from 'react';
import ImageFocusPoint from '../ImageFocusPoint';

test('Should render Loader at the beginning', () => {
    const value = {x: 0, y: 0};
    const imageFocusPoint = mount(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={value}
        />
    );

    expect(imageFocusPoint.render()).toMatchSnapshot();
});

test('Should render focus point cells with correct size', () => {
    const value = {x: 0, y: 0};
    const imageFocusPoint = mount(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={value}
        />
    );

    imageFocusPoint.find('img').prop('onLoad')({
        currentTarget: {
            getBoundingClientRect: jest.fn().mockReturnValue({width: '300px', height: '200px'}),
        },
    });

    expect(imageFocusPoint.render()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the top-left point', () => {
    const value = {x: 0, y: 0};
    const imageFocusPoint = mount(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={value}
        />
    );

    imageFocusPoint.find('img').simulate('load');
    expect(imageFocusPoint.render()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the top-center point', () => {
    const value = {x: 1, y: 0};
    const imageFocusPoint = mount(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={value}
        />
    );

    imageFocusPoint.find('img').simulate('load');
    expect(imageFocusPoint.render()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the top-right point', () => {
    const value = {x: 2, y: 0};
    const imageFocusPoint = mount(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={value}
        />
    );

    imageFocusPoint.find('img').simulate('load');
    expect(imageFocusPoint.render()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the center-left point', () => {
    const value = {x: 0, y: 1};
    const imageFocusPoint = mount(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={value}
        />
    );

    imageFocusPoint.find('img').simulate('load');
    expect(imageFocusPoint.render()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the center-center point', () => {
    const value = {x: 1, y: 1};
    const imageFocusPoint = mount(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={value}
        />
    );

    imageFocusPoint.find('img').simulate('load');
    expect(imageFocusPoint.render()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the center-right point', () => {
    const value = {x: 2, y: 1};
    const imageFocusPoint = mount(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={value}
        />
    );

    imageFocusPoint.find('img').simulate('load');
    expect(imageFocusPoint.render()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the bottom-left point', () => {
    const value = {x: 0, y: 2};
    const imageFocusPoint = mount(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={value}
        />
    );

    imageFocusPoint.find('img').simulate('load');
    expect(imageFocusPoint.render()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the bottom-center point', () => {
    const value = {x: 1, y: 2};
    const imageFocusPoint = mount(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={value}
        />
    );

    imageFocusPoint.find('img').simulate('load');
    expect(imageFocusPoint.render()).toMatchSnapshot();
});

test('Should render ImageFocusPoint with focusing the bottom-right point', () => {
    const value = {x: 2, y: 2};
    const imageFocusPoint = mount(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={value}
        />
    );

    imageFocusPoint.find('img').simulate('load');
    expect(imageFocusPoint.render()).toMatchSnapshot();
});

test('Should call the onClick handler when a focus point was clicked', () => {
    const changeSpy = jest.fn();
    const value = {x: 1, y: 1};
    const imageFocusPoint = mount(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={changeSpy}
            value={value}
        />
    );

    imageFocusPoint.find('img').simulate('load');

    imageFocusPoint.find('button').at(0).simulate('click');
    expect(changeSpy).toBeCalledWith({x: 0, y: 0});

    imageFocusPoint.find('button').at(1).simulate('click');
    expect(changeSpy).toBeCalledWith({x: 1, y: 0});

    imageFocusPoint.find('button').at(3).simulate('click');
    expect(changeSpy).toBeCalledWith({x: 0, y: 1});
});

test('Should disable the selected focus point button', () => {
    const value = {x: 0, y: 0};
    const imageFocusPoint = mount(
        <ImageFocusPoint
            image="http://lorempixel.com/300/300"
            onChange={jest.fn()}
            value={value}
        />
    );

    imageFocusPoint.find('img').simulate('load');

    expect(imageFocusPoint.find('button').at(0).props().disabled).toBe(true);
});
