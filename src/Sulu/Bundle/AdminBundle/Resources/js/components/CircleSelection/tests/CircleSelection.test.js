// @flow
import React from 'react';
import {render} from '@testing-library/react';
import CircleSelection from '../CircleSelection';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../withContainerSize/withContainerSize');
jest.mock('../../../utils/DOM/afterElementsRendered');
jest.mock('../ModifiableCircle', () => {
    const React = require('react');

    return jest.fn(function ModifiableCircleMock() {
        return React.createElement('div', {'data-testid': 'modifiable-circle'});
    });
});

const modifiableCircle = ((jest.requireMock('../ModifiableCircle'): any): {
    mock: {calls: Array<[Object]>},
    ...
});

function getLastModifiableCircleProps(): any {
    return getLatestMockProps(modifiableCircle);
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('The component should render', () => {
    const {asFragment} = render(
        <CircleSelection onChange={jest.fn()} onFinish={jest.fn()} value={{radius: 1, top: 3, left: 4}}>
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('The component should center and maximize the selection if no value is given', () => {
    const changeSpy = jest.fn();

    render(
        <CircleSelection onChange={changeSpy} onFinish={jest.fn()} value={undefined}>
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    expect(changeSpy).toHaveBeenCalledWith({left: 320, radius: 180, top: 180});
});

test('The component should reset the value if modifiable circle is doubleclicked', () => {
    const changeSpy = jest.fn();

    render(
        <CircleSelection
            minHeight={100}
            minWidth={100}
            onChange={changeSpy}
            onFinish={jest.fn()}
            value={{radius: 100, top: 30, left: 40}}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    getLastModifiableCircleProps().onDoubleClick();

    expect(changeSpy).toHaveBeenCalledWith({left: 320, radius: 180, top: 180});
});

test('The component should center and maximize the selection when a minRadius and maxRadius is given', () => {
    const changeSpy = jest.fn();

    render(
        <CircleSelection
            maxRadius={200}
            minRadius={50}
            onChange={changeSpy}
            onFinish={jest.fn()}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    expect(changeSpy).toHaveBeenCalledWith({left: 320, radius: 180, top: 180});
});

test('The component should not allow the selection to move over the borders', () => {
    const changeSpy = jest.fn();

    render(
        <CircleSelection onChange={changeSpy} onFinish={jest.fn()} value={{left: 0, top: 0, radius: 50}}>
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    getLastModifiableCircleProps().onChange({radius: 0, left: -10, top: -20});

    expect(changeSpy).toHaveBeenCalledWith({radius: 50, top: 0, left: 0});
});

test('The component should not allow the selection to be bigger than the container', () => {
    const changeSpy = jest.fn();

    render(
        <CircleSelection onChange={changeSpy} onFinish={jest.fn()} value={{left: 0, top: 0, radius: 2000}}>
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    getLastModifiableCircleProps().onChange({radius: 5000, left: 0, top: 0});

    expect(changeSpy).toHaveBeenCalledWith({radius: 734, top: 0, left: 0});
});

test('The component should not round if told by the properties', () => {
    const changeSpy = jest.fn();

    render(
        <CircleSelection
            minRadius={5}
            onChange={changeSpy}
            onFinish={jest.fn()}
            round={false}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    const props = getLastModifiableCircleProps();
    expect(props.top).toBeGreaterThan(166);
    expect(props.left).toBeGreaterThan(166);
    expect(props.radius).toBeGreaterThan(166);
});

test('The component should work with percentage values if told by the properties', () => {
    const changeSpy = jest.fn();

    render(
        <CircleSelection
            onChange={changeSpy}
            onFinish={jest.fn()}
            usePercentageValues={true}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    expect(changeSpy).toHaveBeenCalledWith({top: 0.5, left: 0.5, radius: 0.28125});
});

test('The component should call onFinish', () => {
    const finishSpy = jest.fn();

    render(
        <CircleSelection onChange={jest.fn()} onFinish={finishSpy} value={undefined}>
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    getLastModifiableCircleProps().onFinish();
    expect(finishSpy).toHaveBeenCalled();
});
