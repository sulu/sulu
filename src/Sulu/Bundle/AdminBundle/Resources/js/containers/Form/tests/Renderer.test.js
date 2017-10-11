/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import fieldRegistry from '../registries/FieldRegistry';
import Renderer from '../Renderer';

jest.mock('../registries/FieldRegistry', () => ({
    get: jest.fn(),
}));

test('Should render a form tag', () => {
    const renderer = render(<Renderer schema={{}} />);
    expect(renderer).toMatchSnapshot();
});

test('Should prevent default submit handling', () => {
    const preventDefaultSpy = jest.fn();
    const submitSpy = jest.fn();
    const renderer = shallow(<Renderer schema={{}} onSubmit={submitSpy} />);

    renderer.find('form').simulate('submit', {preventDefault: preventDefaultSpy});
    expect(preventDefaultSpy).toBeCalled();
});

test('Should call onSubmit callback when submitted', () => {
    const submitSpy = jest.fn();
    const renderer = mount(<Renderer schema={{}} onSubmit={submitSpy} />);

    renderer.instance().submit();
    expect(submitSpy).toBeCalled();
});

test('Should render field types based on schema', () => {
    const schema = {
        text: {
            label: 'Text',
            type: 'text',
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
        },
    };

    fieldRegistry.get.mockImplementation((type) => {
        switch (type) {
            case 'text':
                return function Text() {
                    return <input type="text" />;
                };
            case 'datetime':
                return function DateTime() {
                    return <input type="datetime" />;
                };
        }
    });

    const renderer = render(<Renderer schema={schema} data={{}} />);

    expect(renderer).toMatchSnapshot();
});

test('Should pass name and schema to fields', () => {
    const schema = {
        text: {
            label: 'Text',
            type: 'text',
        },
        datetime: {
            label: 'Datetime',
            type: 'datetime',
        },
    };

    const onChangeSpy = jest.fn();

    const renderer = shallow(<Renderer schema={schema} data={{}} onChange={onChangeSpy} />);

    const fields = renderer.find('Field');

    expect(fields.at(0).prop('name')).toBe('text');
    expect(fields.at(0).prop('onChange')).toBe(onChangeSpy);
    expect(fields.at(1).prop('name')).toBe('datetime');
    expect(fields.at(1).prop('onChange')).toBe(onChangeSpy);
});
