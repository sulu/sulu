// @flow
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import RectangleSelection from '../RectangleSelection';

const mockModifiableRectangle = jest.fn();

jest.mock('../../../utils/Translator');

jest.mock('../../withContainerSize/withContainerSize');
jest.mock('../ModifiableRectangle', () => function MockModifiableRectangle(props) {
    mockModifiableRectangle(props);

    return (
        <div
            data-height={props.height}
            data-left={props.left}
            data-testid="modifiable-rectangle"
            data-top={props.top}
            data-width={props.width}
        >
            <button onClick={props.onDoubleClick} type="button">
                Double click
            </button>
            <button onClick={props.onFinish} type="button">
                Finish
            </button>
        </div>
    );
});

beforeEach(() => {
    jest.clearAllMocks();
});

function renderRectangleSelection(props = {}) {
    return render(
        <RectangleSelection
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={undefined}
            {...props}
        >
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );
}

function getLatestModifiableRectangleProps() {
    return mockModifiableRectangle.mock.calls[mockModifiableRectangle.mock.calls.length - 1][0];
}

test('The component should render with children', () => {
    const {asFragment} = renderRectangleSelection();

    expect(asFragment()).toMatchSnapshot();
});

test('The component should render with value as selection', () => {
    const {asFragment} = renderRectangleSelection({
        value: {width: 1, height: 2, top: 3, left: 4},
    });

    expect(asFragment()).toMatchSnapshot();
});

test('The component should render with minimum size notification', () => {
    const {asFragment} = renderRectangleSelection({
        minHeight: 200,
        minWidth: 100,
        value: {width: 100, height: 200, top: 30, left: 40},
    });

    expect(asFragment()).toMatchSnapshot();
});

test('The component should render without minimum size notification', () => {
    const {asFragment} = renderRectangleSelection({
        minHeight: 100,
        minWidth: 100,
        value: {width: 100, height: 200, top: 30, left: 40},
    });

    expect(asFragment()).toMatchSnapshot();
});

test('The component should reset the value if modifiable rectangle is doubleclicked', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    renderRectangleSelection({
        minHeight: 100,
        minWidth: 100,
        onChange: changeSpy,
        value: {width: 100, height: 200, top: 30, left: 40},
    });

    await user.click(screen.getByRole('button', {name: 'Double click'}));
    expect(changeSpy).toHaveBeenCalledWith({height: 360, left: 140, top: 0, width: 360});
});

test('The component should center and maximize the selection when a minHeight and minWidth is given', () => {
    const {asFragment} = renderRectangleSelection({
        minHeight: 50,
        minWidth: 200,
        value: undefined,
    });

    expect(asFragment()).toMatchSnapshot();
});

test('The component should not allow the selection to move over the borders', () => {
    const changeSpy = jest.fn();

    renderRectangleSelection({
        onChange: changeSpy,
        value: {height: 360, left: 0, top: 0, width: 640},
    });

    act(() => {
        getLatestModifiableRectangleProps().onChange({
            width: 0,
            height: 0,
            left: -10,
            top: -20,
        });
    });
    expect(changeSpy).toHaveBeenCalledWith({width: 640, height: 360, top: 0, left: 0});
});

test('The component should not allow the selection to be bigger than the container', () => {
    const changeSpy = jest.fn();

    renderRectangleSelection({
        onChange: changeSpy,
        value: {height: 1000, left: 0, top: 0, width: 2000},
    });

    act(() => {
        getLatestModifiableRectangleProps().onChange({
            width: 10,
            height: 20,
            left: 0,
            top: 0,
        });
    });
    expect(changeSpy).toHaveBeenCalledWith({width: 640, height: 360, top: 0, left: 0});
});

test('The component should enforce a ratio on the selection if minWidth and minHeight are given', () => {
    const changeSpy = jest.fn();

    renderRectangleSelection({
        minHeight: 20,
        minWidth: 10,
        onChange: changeSpy,
        value: {height: 360, left: 0, top: 0, width: 640},
    });

    act(() => {
        getLatestModifiableRectangleProps().onChange({
            width: -10,
            height: -250,
            left: 0,
            top: 0,
        });
    });
    expect(changeSpy).toHaveBeenCalledWith(expect.objectContaining({width: 55, height: 110}));
});

test(
    'The component should allow selections across the whole container' +
    'even if a minValue is bigger than actual container size',
    () => {
        const changeSpy = jest.fn();

        renderRectangleSelection({
            minHeight: 180,
            minWidth: 1280,
            onChange: changeSpy,
            value: {height: 90, left: 0, top: 0, width: 640},
        });

        act(() => {
            getLatestModifiableRectangleProps().onChange({
                width: 0,
                height: 0,
                left: 0,
                top: 360,
            });
        });
        expect(changeSpy).toHaveBeenCalledWith(expect.objectContaining({top: 360 - 90}));
    }
);

test('The component should not round if told by the properties', () => {
    const changeSpy = jest.fn();

    renderRectangleSelection({
        minHeight: 1,
        minWidth: 3,
        onChange: changeSpy,
        round: false,
        value: undefined,
    });

    const modifiableRectangleProps = getLatestModifiableRectangleProps();
    expect(modifiableRectangleProps.top).toBeGreaterThan(73);
    expect(modifiableRectangleProps.top).toBeLessThan(74);
    expect(modifiableRectangleProps.height).toBeGreaterThan(213);
    expect(modifiableRectangleProps.height).toBeLessThan(214);
});

test('The component should work with percentage values if told by the properties', () => {
    const changeSpy = jest.fn();

    renderRectangleSelection({
        onChange: changeSpy,
        usePercentageValues: true,
        value: undefined,
    });

    expect(changeSpy).toHaveBeenCalledWith({top: 0, left: 0, width: 1, height: 1});
});

test('The component should call onFinish', async() => {
    const user = userEvent.setup();
    const finishSpy = jest.fn();

    renderRectangleSelection({
        onChange: jest.fn(),
        onFinish: finishSpy,
        value: undefined,
    });

    await user.click(screen.getByRole('button', {name: 'Finish'}));
    expect(finishSpy).toHaveBeenCalled();
});
