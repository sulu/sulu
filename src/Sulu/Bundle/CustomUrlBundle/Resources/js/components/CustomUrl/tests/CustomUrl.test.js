// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import CustomUrl from '../../CustomUrl';

test('Render with empty placeholder', () => {
    expect(render(<CustomUrl baseDomain="*.sulu.io/*" onChange={jest.fn()} value={[]} />)).toMatchSnapshot();
});

test('Render with partially filled placeholder', () => {
    expect(render(<CustomUrl baseDomain="*.*.sulu.io" onChange={jest.fn()} value={['test']} />)).toMatchSnapshot();
});

test('Render with completely filled placeholder', () => {
    expect(render(<CustomUrl baseDomain="sulu.io/*/*" onChange={jest.fn()} value={['test1', 'test2']} />))
        .toMatchSnapshot();
});

test('Call onBlur for every input field', () => {
    const blurSpy = jest.fn();
    const customUrl = mount(<CustomUrl baseDomain="*.sulu.io/*" onBlur={blurSpy} onChange={jest.fn()} value={[]} />);

    expect(blurSpy).not.toBeCalled();

    customUrl.find('Input').at(0).prop('onBlur')();
    expect(blurSpy).toHaveBeenLastCalledWith();

    customUrl.find('Input').at(1).prop('onBlur')();
    expect(blurSpy).toHaveBeenLastCalledWith();

    expect(blurSpy).toHaveBeenCalledTimes(2);
});

test('Call onChange after change of every input field', () => {
    const changeSpy = jest.fn();
    const customUrl = mount(<CustomUrl baseDomain="*.sulu.io/*" onChange={changeSpy} value={[]} />);

    expect(changeSpy).not.toBeCalled();

    customUrl.find('Input').at(0).prop('onChange')('test1');
    expect(changeSpy).toHaveBeenLastCalledWith(['test1']);

    customUrl.find('Input').at(1).prop('onChange')('test2');
    expect(changeSpy).toHaveBeenLastCalledWith([undefined, 'test2']);
});
