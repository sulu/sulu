// @flow
import React from 'react';
import {shallow} from 'enzyme';
import Url from '../Url';

test('Set the correct values for protocol and path when initializing', () => {
    const url = shallow(<Url onChange={jest.fn()} protocols={['http://', 'https://']} value="https://www.sulu.io" />);

    expect(url.find('SingleSelect').prop('value')).toEqual('https://');
    expect(url.find('input').prop('value')).toEqual('www.sulu.io');
});

test('Set the correct values for protocol and path when updating', () => {
    const url = shallow(<Url onChange={jest.fn()} protocols={['http://', 'https://']} value="https://www.sulu.io" />);

    expect(url.find('SingleSelect').prop('value')).toEqual('https://');
    expect(url.find('input').prop('value')).toEqual('www.sulu.io');

    url.setProps({value: 'http://sulu.at'});

    expect(url.find('SingleSelect').prop('value')).toEqual('http://');
    expect(url.find('input').prop('value')).toEqual('sulu.at');
});

test('Call disposer when unmounted', () => {
    const url = shallow(<Url onChange={jest.fn()} protocols={[]} value={undefined} />);
    const changeDisposerSpy = jest.fn();
    url.instance().changeDisposer = changeDisposerSpy;

    url.unmount();

    expect(changeDisposerSpy).toBeCalledWith();
});

test('Call onChange callback with the first protocol if none was selected', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} protocols={['http://', 'https://']} value={undefined} />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'sulu.at',
        },
    });

    expect(changeSpy).toBeCalledWith('http://sulu.at');
});

test('Call onChange callback when protocol was changed', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} protocols={['http://', 'https://']} value="https://www.sulu.io" />);
    url.find('SingleSelect').prop('onChange')('http://');

    expect(changeSpy).toBeCalledWith('http://www.sulu.io');
});

test('Call onChange callback when path was changed', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} protocols={['http://', 'https://']} value="https://www.sulu.io" />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'sulu.at',
        },
    });

    expect(changeSpy).toBeCalledWith('https://sulu.at');
});

test('Should remove the protocol from path and set it on the protocol select', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} protocols={['http://', 'https://']} value={undefined} />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'http://www.sulu.at',
        },
    });

    expect(url.find('SingleSelect').prop('value')).toEqual('http://');
    expect(url.find('input').prop('value')).toEqual('www.sulu.at');
});

test('Should remove the protocol from path and set it on the protocol select if protocol is already selected', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} protocols={['http://', 'https://']} value="http://www.sulu.at" />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'https://www.sulu.io',
        },
    });

    expect(url.find('SingleSelect').prop('value')).toEqual('https://');
    expect(url.find('input').prop('value')).toEqual('www.sulu.io');
});

test('Call onBlur callback when protocol was changed', () => {
    const blurSpy = jest.fn();
    const url = shallow(
        <Url
            onBlur={blurSpy}
            onChange={jest.fn()}
            protocols={['http://', 'https://']}
            value="https://www.sulu.io"
        />
    );
    url.find('SingleSelect').prop('onChange')('http://');

    expect(blurSpy).toBeCalledWith();
});

test('Call onChange callback when path was changed', () => {
    const blurSpy = jest.fn();
    const url = shallow(
        <Url
            onBlur={blurSpy}
            onChange={jest.fn()}
            protocols={['http://', 'https://']}
            value="https://www.sulu.io"
        />
    );
    url.find('input').prop('onBlur')();

    expect(blurSpy).toBeCalledWith();
});
