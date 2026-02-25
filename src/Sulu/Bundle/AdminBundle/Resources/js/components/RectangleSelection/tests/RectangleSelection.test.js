// @flow
import React from 'react';
import {render} from '@testing-library/react';
import RectangleSelection from '../RectangleSelection';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../ModifiableRectangle', () => jest.fn(() => <div data-testid="modifiable-rectangle" />));
jest.mock('../../withContainerSize/withContainerSize');
jest.mock('../../../utils/DOM/afterElementsRendered');

const ModifiableRectangleMock: any = jest.requireMock('../ModifiableRectangle');

beforeEach(() => {
    jest.clearAllMocks();
});

test('The component should render with children', () => {
    const view = render(
        <RectangleSelection
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(view.asFragment()).toMatchSnapshot();
});

test('The component should render with value as selection', () => {
    const view = render(
        <RectangleSelection
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={{width: 1, height: 2, top: 3, left: 4}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(view.asFragment()).toMatchSnapshot();
});

test('The component should render with minimum size notification', () => {
    const view = render(
        <RectangleSelection
            minHeight={200}
            minWidth={100}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={{width: 100, height: 200, top: 30, left: 40}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(view.asFragment()).toMatchSnapshot();
});

test('The component should render without minimum size notification', () => {
    const view = render(
        <RectangleSelection
            minHeight={100}
            minWidth={100}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={{width: 100, height: 200, top: 30, left: 40}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(view.asFragment()).toMatchSnapshot();
});

test('The component should reset the value if modifiable rectangle is doubleclicked', () => {
    const changeSpy = jest.fn();

    render(
        <RectangleSelection
            minHeight={100}
            minWidth={100}
            onChange={changeSpy}
            onFinish={jest.fn()}
            value={{width: 100, height: 200, top: 30, left: 40}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    getLatestMockProps(ModifiableRectangleMock).onDoubleClick();

    expect(changeSpy).toBeCalledWith({height: 360, left: 140, top: 0, width: 360});
});

test('The component should center and maximize the selection when a minHeight and minWidth is given', () => {
    const view = render(
        <RectangleSelection
            minHeight={50}
            minWidth={200}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(view.asFragment()).toMatchSnapshot();
});

test('The component should not allow the selection to move over the borders', () => {
    const changeSpy = jest.fn();

    render(
        <RectangleSelection
            onChange={changeSpy}
            onFinish={jest.fn()}
            value={{height: 360, left: 0, top: 0, width: 640}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    getLatestMockProps(ModifiableRectangleMock).onChange({
        width: 0,
        height: 0,
        left: -10,
        top: -20,
    });
    expect(changeSpy).toBeCalledWith({width: 640, height: 360, top: 0, left: 0});
});

test('The component should not allow the selection to be bigger than the container', () => {
    const changeSpy = jest.fn();

    render(
        <RectangleSelection
            onChange={changeSpy}
            onFinish={jest.fn()}
            value={{height: 1000, left: 0, top: 0, width: 2000}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    getLatestMockProps(ModifiableRectangleMock).onChange({
        width: 10,
        height: 20,
        left: 0,
        top: 0,
    });
    expect(changeSpy).toBeCalledWith({width: 640, height: 360, top: 0, left: 0});
});

test('The component should enforce a ratio on the selection if minWidth and minHeight are given', () => {
    const changeSpy = jest.fn();

    render(
        <RectangleSelection
            minHeight={20}
            minWidth={10}
            onChange={changeSpy}
            onFinish={jest.fn()}
            value={{height: 360, left: 0, top: 0, width: 640}}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    getLatestMockProps(ModifiableRectangleMock).onChange({
        width: -10,
        height: -250,
        left: 0,
        top: 0,
    });
    expect(changeSpy).toBeCalledWith(expect.objectContaining({width: 55, height: 110}));
});

test(
    'The component should allow selections across the whole container' +
    'even if a minValue is bigger than actual container size',
    () => {
        const changeSpy = jest.fn();

        render(
            <RectangleSelection
                minHeight={180}
                minWidth={1280}
                onChange={changeSpy}
                onFinish={jest.fn()}
                value={{height: 90, left: 0, top: 0, width: 640}}
            >
                <p>Lorem ipsum</p>
            </RectangleSelection>
        );

        getLatestMockProps(ModifiableRectangleMock).onChange({
            width: 0,
            height: 0,
            left: 0,
            top: 360,
        });
        expect(changeSpy).toBeCalledWith(expect.objectContaining({top: 360 - 90}));
    }
);

test('The component should not round if told by the properties', () => {
    render(
        <RectangleSelection
            minHeight={1}
            minWidth={3}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            round={false}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(getLatestMockProps(ModifiableRectangleMock).top).toBeGreaterThan(73);
    expect(getLatestMockProps(ModifiableRectangleMock).top).toBeLessThan(74);
    expect(getLatestMockProps(ModifiableRectangleMock).height).toBeGreaterThan(213);
    expect(getLatestMockProps(ModifiableRectangleMock).height).toBeLessThan(214);
});

test('The component should work with percentage values if told by the properties', () => {
    const changeSpy = jest.fn();

    render(
        <RectangleSelection
            onChange={changeSpy}
            onFinish={jest.fn()}
            usePercentageValues={true}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(changeSpy).toBeCalledWith({top: 0, left: 0, width: 1, height: 1});
});

test('The component should call onFinish', () => {
    const finishSpy = jest.fn();

    render(
        <RectangleSelection
            onChange={jest.fn()}
            onFinish={finishSpy}
            value={undefined}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    getLatestMockProps(ModifiableRectangleMock).onFinish();
    expect(finishSpy).toBeCalled();
});
