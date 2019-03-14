// @flow
import {mount, render} from 'enzyme';
import React from 'react';
import {RectangleSelection} from '../RectangleSelection';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../withContainerSize/withContainerSize');
jest.mock('../../../services/DOM/afterElementsRendered');

test('The component should render with children', () => {
    const view = render(
        <RectangleSelection containerHeight={1000} containerWidth={2000} onChange={jest.fn()} value={undefined}>
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(view).toMatchSnapshot();
});

test('The component should render with value as selection', () => {
    const view = mount(
        <RectangleSelection
            containerHeight={1000}
            containerWidth={2000}
            onChange={jest.fn()}
            value={{width: 1, height: 2, top: 3, left: 4}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(view.render()).toMatchSnapshot();
});

test('The component should render with minimum size notification', () => {
    const view = mount(
        <RectangleSelection
            containerHeight={1000}
            containerWidth={2000}
            minHeight={200}
            minWidth={100}
            onChange={jest.fn()}
            value={{width: 100, height: 200, top: 30, left: 40}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(view.render()).toMatchSnapshot();
});

test('The component should render without minimum size notification', () => {
    const view = mount(
        <RectangleSelection
            containerHeight={1000}
            containerWidth={2000}
            minHeight={100}
            minWidth={100}
            onChange={jest.fn()}
            value={{width: 100, height: 200, top: 30, left: 40}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(view.render()).toMatchSnapshot();
});

test('The component should reset the value if modifiable rectangle is doubleclicked', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <RectangleSelection
            containerHeight={1000}
            containerWidth={2000}
            minHeight={100}
            minWidth={100}
            onChange={changeSpy}
            value={{width: 100, height: 200, top: 30, left: 40}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    view.find('ModifiableRectangle').prop('onDoubleClick')();

    expect(changeSpy).toBeCalledWith(undefined);
});

test('The component should center and maximize the selection when a minHeight and minWidth is given', () => {
    const view = mount(
        <RectangleSelection
            containerHeight={1000}
            containerWidth={2000}
            minHeight={50}
            minWidth={200}
            onChange={jest.fn()}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(view.render()).toMatchSnapshot();
});

test('The component should not allow the selection to move over the borders', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <RectangleSelection
            containerHeight={1000}
            containerWidth={2000}
            onChange={changeSpy}
            value={{height: 2000, left: 0, top: 0, width: 2000}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    view.instance().handleRectangleChange({width: 0, height: 0, left: -10, top: -20});
    expect(changeSpy).toBeCalledWith({width: 2000, height: 1000, top: 0, left: 0});
});

test('The component should not allow the selection to be bigger than the container', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <RectangleSelection
            containerHeight={1000}
            containerWidth={2000}
            onChange={changeSpy}
            value={{height: 1000, left: 0, top: 0, width: 2000}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    view.instance().handleRectangleChange({width: 10, height: 20, left: 0, top: 0});
    expect(changeSpy).toBeCalledWith({width: 2000, height: 1000, top: 0, left: 0});
});

test('The component should enforce a ratio on the selection if minWidth and minHeight are given', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <RectangleSelection
            containerHeight={1000}
            containerWidth={2000}
            minHeight={20}
            minWidth={10}
            onChange={changeSpy}
            value={{height: 1000, left: 0, top: 0, width: 2000}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    view.instance().handleRectangleChange({width: -10, height: -250, left: 0, top: 0});
    expect(changeSpy).toBeCalledWith(expect.objectContaining({width: 375, height: 750}));
});

test('The component should not round if told by the properties', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <RectangleSelection
            containerHeight={1000}
            containerWidth={2000}
            minHeight={1}
            minWidth={3}
            onChange={changeSpy}
            round={false}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(view.find('ModifiableRectangle').prop('top')).toBeGreaterThan(166);
    expect(view.find('ModifiableRectangle').prop('top')).toBeLessThan(167);
    expect(view.find('ModifiableRectangle').prop('height')).toBeGreaterThan(666);
    expect(view.find('ModifiableRectangle').prop('height')).toBeLessThan(1667);
});
