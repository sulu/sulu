// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

test('Call onBlur for every input field', async() => {
    const user = userEvent.setup();
    const blurSpy = jest.fn();

    render(<CustomUrl baseDomain="*.sulu.io/*" onBlur={blurSpy} onChange={jest.fn()} value={[]} />);
    const inputs = screen.getAllByRole('textbox');

    expect(blurSpy).not.toHaveBeenCalled();

    await user.click(inputs[0]);
    await user.tab();
    expect(blurSpy).toHaveBeenCalledTimes(1);

    await user.click(inputs[1]);
    await user.tab();
    expect(blurSpy).toHaveBeenCalledTimes(2);
});

test('Call onChange after change of every input field', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    const {rerender} = render(<CustomUrl baseDomain="*.sulu.io/*" onChange={changeSpy} value={[]} />);
    changeSpy.mockImplementation((value) => {
        rerender(<CustomUrl baseDomain="*.sulu.io/*" onChange={changeSpy} value={value} />);
    });
    const inputs = screen.getAllByRole('textbox');

    expect(changeSpy).not.toHaveBeenCalled();

    await user.type(inputs[0], 'test1');
    expect(changeSpy).toHaveBeenLastCalledWith(['test1']);

    rerender(<CustomUrl baseDomain="*.sulu.io/*" onChange={changeSpy} value={[]} />);
    await user.type(screen.getAllByRole('textbox')[1], 'test2');
    expect(changeSpy).toHaveBeenLastCalledWith([undefined, 'test2']);
});
