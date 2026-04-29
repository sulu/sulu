// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import React from 'react';
import CustomUrl from '../../CustomUrl';

test('Render with empty placeholder', () => {
    const {asFragment} = render(<CustomUrl baseDomain="*.sulu.io/*" onChange={jest.fn()} value={[]} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render with partially filled placeholder', () => {
    const {asFragment} = render(<CustomUrl baseDomain="*.*.sulu.io" onChange={jest.fn()} value={['test']} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render with completely filled placeholder', () => {
    const {asFragment} = render(<CustomUrl baseDomain="sulu.io/*/*" onChange={jest.fn()} value={['test1', 'test2']} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Call onBlur for every input field', () => {
    const blurSpy = jest.fn();

    render(<CustomUrl baseDomain="*.sulu.io/*" onBlur={blurSpy} onChange={jest.fn()} value={[]} />);
    const inputs = screen.getAllByRole('textbox');

    expect(blurSpy).not.toBeCalled();

    fireEvent.blur(inputs[0]);
    expect(blurSpy).toHaveBeenCalledTimes(1);

    fireEvent.blur(inputs[1]);
    expect(blurSpy).toHaveBeenCalledTimes(2);
});

test('Call onChange after change of every input field', () => {
    const changeSpy = jest.fn();

    render(<CustomUrl baseDomain="*.sulu.io/*" onChange={changeSpy} value={[]} />);
    const inputs = screen.getAllByRole('textbox');

    expect(changeSpy).not.toBeCalled();

    // eslint-disable-next-line testing-library/prefer-user-event
    fireEvent.change(inputs[0], {target: {value: 'test1'}});
    expect(changeSpy).toHaveBeenLastCalledWith(['test1']);

    // eslint-disable-next-line testing-library/prefer-user-event
    fireEvent.change(inputs[1], {target: {value: 'test2'}});
    expect(changeSpy).toHaveBeenLastCalledWith([undefined, 'test2']);
});
