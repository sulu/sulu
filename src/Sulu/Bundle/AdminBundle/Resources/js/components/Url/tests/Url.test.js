// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import log from 'loglevel';
import Url from '../Url';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

test('Render the component with an error', () => {
    expect(render(
        <Url onChange={jest.fn()} protocols={['http://', 'https://']} valid={false} value={undefined} />
    )).toMatchSnapshot();
});

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

test('Should log a warning if a not available protocol has been given', () => {
    const url = shallow(<Url onChange={jest.fn()} protocols={['http://']} value="https://www.sulu.io" />);

    expect(url.find('SingleSelect').prop('value')).toEqual(undefined);
    expect(url.find('input').prop('value')).toEqual('https://www.sulu.io');
    expect(log.warn).toBeCalled();
});

test('Show error when invalid URL was passed via updated prop', () => {
    const url = shallow(<Url onChange={jest.fn()} protocols={['http://', 'https://']} value={undefined} />);
    expect(url.find('.error')).toHaveLength(0);

    url.setProps({value: 'http://su lu.at'});

    expect(url.find('.error')).toHaveLength(1);
});

test('Should not reset value of protocol select when undefined value is passed', () => {
    const url = shallow(<Url onChange={jest.fn()} protocols={['http://', 'https://', 'ftp://']} value="https://" />);
    expect(url.find('SingleSelect').prop('value')).toEqual('https://');
    expect(url.find('input').prop('value')).toEqual('');

    url.setProps({value: undefined});
    expect(url.find('SingleSelect').prop('value')).toEqual('https://');
    expect(url.find('input').prop('value')).toEqual('');
});

test('Remove error when valid URL was passed via updated prop', () => {
    const url = shallow(<Url onChange={jest.fn()} protocols={['http://', 'https://']} value="http://su lu.at" />);
    expect(url.find('.error')).toHaveLength(1);

    url.setProps({value: 'http://sulu.at'});

    expect(url.find('.error')).toHaveLength(0);
});

test('Remove error when valid URL was changed using the text field', () => {
    const url = shallow(<Url onChange={jest.fn()} protocols={['http://', 'https://']} value="http://su lu.at" />);
    expect(url.find('.error')).toHaveLength(1);

    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'sulu.at',
        },
    });
    url.find('input').prop('onBlur')();

    expect(url.find('.error')).toHaveLength(0);
});

test('Call onChange callback with the first protocol if none was selected', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} protocols={['http://', 'https://']} value={undefined} />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'sulu.at',
        },
    });
    url.find('input').prop('onBlur')();

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
    url.find('input').prop('onBlur')();

    expect(changeSpy).toBeCalledWith('https://sulu.at');
});

test('Do not call onChange callback when path was changed but not blurred', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} protocols={['http://', 'https://']} value="https://www.sulu.io" />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'sulu.at',
        },
    });

    expect(changeSpy).not.toBeCalled();
});

test('Call onChange callback with undefined if URL is not valid but leave the current value', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} protocols={['http://', 'https://']} value="https://www.sulu.io" />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'su lu.at',
        },
    });
    url.find('input').prop('onBlur')();

    expect(changeSpy).toBeCalledWith(undefined);
    expect(url.find('SingleSelect').prop('value')).toEqual('https://');
    expect(url.find('input').prop('value')).toEqual('su lu.at');
    expect(url.find('.error')).toHaveLength(1);
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

test('Call onBlur callback when path was changed', () => {
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
