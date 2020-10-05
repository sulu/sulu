// @flow
import {mount} from 'enzyme';
import React from 'react';
import CircleSelection from '../CircleSelection';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../withContainerSize/withContainerSize');
jest.mock('../../../utils/DOM/afterElementsRendered');

test('The component should render', () => {
    const view = mount(
        <CircleSelection
            // containerHeight={360}
            // containerWidth={640}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={{radius: 1, top: 3, left: 4}}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    expect(view.render()).toMatchSnapshot();
});

test('The component should center and maximize the selection if no value is given', () => {
    const changeSpy = jest.fn();

    mount(
        <CircleSelection
            // containerHeight={360}
            // containerWidth={640}
            onChange={changeSpy}
            onFinish={jest.fn()}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    expect(changeSpy).toBeCalledWith({left: 320, radius: 180, top: 180});
});

test('The component should reset the value if modifiable circle is doubleclicked', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <CircleSelection
            // containerHeight={360}
            // containerWidth={640}
            minHeight={100}
            minWidth={100}
            onChange={changeSpy}
            onFinish={jest.fn()}
            value={{radius: 100, top: 30, left: 40}}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    view.find('ModifiableCircle').prop('onDoubleClick')();

    expect(changeSpy).toBeCalledWith({left: 320, radius: 180, top: 180});
});

test('The component should center and maximize the selection when a minRadius and maxRadius is given', () => {
    const changeSpy = jest.fn();

    mount(
        <CircleSelection
            // containerHeight={360}
            // containerWidth={640}
            maxRadius={200}
            minRadius={50}
            onChange={changeSpy}
            onFinish={jest.fn()}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    expect(changeSpy).toBeCalledWith({left: 320, radius: 180, top: 180});
});

test('The component should not allow the selection to move over the borders', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <CircleSelection
            // containerHeight={360}
            // containerWidth={640}
            onChange={changeSpy}
            onFinish={jest.fn()}
            value={{left: 0, top: 0, radius: 50}}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    view.find('RawCircleSelectionComponent').first().instance().handleCircleChange(
        {radius: 0, left: -10, top: -20}
    );
    expect(changeSpy).toBeCalledWith({radius: 50, top: 0, left: 0});
});

test('The component should not allow the selection to be bigger than the container', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <CircleSelection
            // containerHeight={360}
            // containerWidth={640}
            onChange={changeSpy}
            onFinish={jest.fn()}
            value={{left: 0, top: 0, radius: 2000}}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    view.find('RawCircleSelectionComponent').first().instance().handleCircleChange(
        {radius: 5000, left: 0, top: 0}
    );
    expect(changeSpy).toBeCalledWith({radius: 734, top: 0, left: 0});
});

test('The component should not round if told by the properties', () => {
    const changeSpy = jest.fn();

    const view = mount(
        <CircleSelection
            // containerHeight={360}
            // containerWidth={640}
            minRadius={5}
            onChange={changeSpy}
            onFinish={jest.fn()}
            round={false}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    expect(view.find('ModifiableCircle').prop('top')).toBeGreaterThan(166);
    expect(view.find('ModifiableCircle').prop('left')).toBeGreaterThan(166);
    expect(view.find('ModifiableCircle').prop('radius')).toBeGreaterThan(166);
});

test('The component should work with percentage values if told by the properties', () => {
    const changeSpy = jest.fn();

    mount(
        <CircleSelection
            // containerHeight={360}
            // containerWidth={640}
            onChange={changeSpy}
            onFinish={jest.fn()}
            usePercentageValues={true}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    expect(changeSpy).toBeCalledWith({top: 0.5, left: 0.5, radius: 0.28125});
});

test('The component should call onFinish', () => {
    const finishSpy = jest.fn();

    const circleSelection = mount(
        <CircleSelection
            // containerHeight={360}
            // containerWidth={640}
            onChange={jest.fn()}
            onFinish={finishSpy}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );

    circleSelection.find('ModifiableCircle').props().onFinish();
    expect(finishSpy).toBeCalled();
});
