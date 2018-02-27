// @flow
import React from 'react';
import {shallow} from 'enzyme';
import SingleSelect from '../../fields/SingleSelect';

test('Pass props correctly to SingleSelect', () => {
    const options = {
        values: [
            {
                value: 'mr',
                name: 'Mister',
            },
            {
                value: 'ms',
                name: 'Miss',
            },
        ],
    };
    const singleSelect = shallow(
        <SingleSelect
            onChange={jest.fn()}
            onFinish={jest.fn()}
            options={options}
            value="test"
        />
    );

    expect(singleSelect.prop('value')).toBe('test');
    expect(singleSelect.find('Option').at(0).props()).toEqual(expect.objectContaining({
        value: 'mr',
        children: 'Mister',
    }));
    expect(singleSelect.find('Option').at(1).props()).toEqual(expect.objectContaining({
        value: 'ms',
        children: 'Miss',
    }));
});

test('Should call onFinish callback on every onChange', () => {
    const finishSpy = jest.fn();
    const options = {
        values: [
            {
                value: 'mr',
                name: 'Mister',
            },
            {
                value: 'ms',
                name: 'Miss',
            },
        ],
    };

    const singleSelect = shallow(
        <SingleSelect
            onChange={jest.fn()}
            onFinish={finishSpy}
            options={options}
            value="test"
        />
    );

    singleSelect.simulate('change');

    expect(finishSpy).toBeCalledWith();
});

test('Set default value if no value is passed', () => {
    const changeSpy = jest.fn();
    const options = {
        default_value: 'mr',
        values: [
            {
                value: 'mr',
                name: 'Mister',
            },
            {
                value: 'ms',
                name: 'Miss',
            },
        ],
    };
    shallow(<SingleSelect onChange={changeSpy} onFinish={jest.fn()} options={options} value={undefined} />);

    expect(changeSpy).toBeCalledWith('mr');
});

test('Throw error if no options are passed', () => {
    expect(() => shallow(<SingleSelect onChange={jest.fn()} onFinish={jest.fn()} value={undefined} />))
        .toThrow(/"values"/);
});

test('Throw error if no value option is passed', () => {
    expect(() => shallow(<SingleSelect onChange={jest.fn()} onFinish={jest.fn()} value={undefined} />))
        .toThrow(/"values"/);
});
