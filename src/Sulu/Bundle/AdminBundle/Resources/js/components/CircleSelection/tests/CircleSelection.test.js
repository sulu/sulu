// @flow
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import CircleSelection from '../CircleSelection';

const mockModifiableCircle = jest.fn();

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../withContainerSize/withContainerSize');
jest.mock('../ModifiableCircle', () => function MockModifiableCircle(props) {
    mockModifiableCircle(props);

    return (
        <div
            data-left={props.left}
            data-radius={props.radius}
            data-testid="modifiable-circle"
            data-top={props.top}
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

function renderCircleSelection(props = {}) {
    return render(
        <CircleSelection
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={{radius: 1, top: 3, left: 4}}
            {...props}
        >
            <p>Lorem ipsum</p>
        </CircleSelection>
    );
}

function getLatestModifiableCircleProps() {
    return mockModifiableCircle.mock.calls[mockModifiableCircle.mock.calls.length - 1][0];
}

test('The component should render', () => {
    const {asFragment} = renderCircleSelection();

    expect(asFragment()).toMatchSnapshot();
});

test('The component should center and maximize the selection if no value is given', () => {
    const changeSpy = jest.fn();

    renderCircleSelection({
        onChange: changeSpy,
        value: undefined,
    });

    expect(changeSpy).toHaveBeenCalledWith({left: 320, radius: 180, top: 180});
});

test('The component should reset the value if modifiable circle is doubleclicked', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    renderCircleSelection({
        minHeight: 100,
        minWidth: 100,
        onChange: changeSpy,
        value: {radius: 100, top: 30, left: 40},
    });

    await user.click(screen.getByRole('button', {name: 'Double click'}));
    expect(changeSpy).toHaveBeenCalledWith({left: 320, radius: 180, top: 180});
});

test('The component should center and maximize the selection when a minRadius and maxRadius is given', () => {
    const changeSpy = jest.fn();

    renderCircleSelection({
        maxRadius: 200,
        minRadius: 50,
        onChange: changeSpy,
        value: undefined,
    });

    expect(changeSpy).toHaveBeenCalledWith({left: 320, radius: 180, top: 180});
});

test('The component should not allow the selection to move over the borders', () => {
    const changeSpy = jest.fn();

    renderCircleSelection({
        onChange: changeSpy,
        value: {left: 0, top: 0, radius: 50},
    });

    act(() => {
        getLatestModifiableCircleProps().onChange({radius: 0, left: -10, top: -20});
    });
    expect(changeSpy).toHaveBeenCalledWith({radius: 50, top: 0, left: 0});
});

test('The component should not allow the selection to be bigger than the container', () => {
    const changeSpy = jest.fn();

    renderCircleSelection({
        onChange: changeSpy,
        value: {left: 0, top: 0, radius: 2000},
    });

    act(() => {
        getLatestModifiableCircleProps().onChange({radius: 5000, left: 0, top: 0});
    });
    expect(changeSpy).toHaveBeenCalledWith({radius: 734, top: 0, left: 0});
});

test('The component should not round if told by the properties', () => {
    const changeSpy = jest.fn();

    renderCircleSelection({
        minRadius: 5,
        onChange: changeSpy,
        round: false,
        value: undefined,
    });

    const modifiableCircleProps = getLatestModifiableCircleProps();
    expect(modifiableCircleProps.top).toBeGreaterThan(166);
    expect(modifiableCircleProps.left).toBeGreaterThan(166);
    expect(modifiableCircleProps.radius).toBeGreaterThan(166);
});

test('The component should work with percentage values if told by the properties', () => {
    const changeSpy = jest.fn();

    renderCircleSelection({
        onChange: changeSpy,
        usePercentageValues: true,
        value: undefined,
    });

    expect(changeSpy).toHaveBeenCalledWith({top: 0.5, left: 0.5, radius: 0.28125});
});

test('The component should call onFinish', async() => {
    const user = userEvent.setup();
    const finishSpy = jest.fn();

    renderCircleSelection({
        onChange: jest.fn(),
        onFinish: finishSpy,
        value: undefined,
    });

    await user.click(screen.getByRole('button', {name: 'Finish'}));
    expect(finishSpy).toHaveBeenCalled();
});
