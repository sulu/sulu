// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import pretty from 'pretty';
import FormBlockCollection from '../FormBlockCollection';

jest.mock('../../Form/registries/FieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'text_line':
                return function TextLine({value}) {
                    return <input type="text" defaultValue={value} />;
                };
        }
    }),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn().mockImplementation((key) => {
        switch (key) {
            case 'sulu_admin.add_block':
                return 'Add block';
        }
    }),
}));

test('Render block with schema', () => {
    const types = {
        default: {
            title: 'Default',
            form: {
                text1: {
                    label: 'Text 1',
                    type: 'text_line',
                },
                text2: {
                    label: 'Text 2',
                    type: 'text_line',
                },
            },
        },
    };

    const value = [
        {
            text1: 'Test 1',
            text2: 'Test 2',
        },
        {
            text1: 'Test 3',
            text2: 'Test 4',
        },
    ];

    const formBlockCollection = mount(<FormBlockCollection onChange={jest.fn()} types={types} value={value} />);

    formBlockCollection.find('Block').at(0).simulate('click');
    formBlockCollection.find('Block').at(1).simulate('click');

    expect(pretty(formBlockCollection.html())).toMatchSnapshot();
});

test('Throw error if no types are passed', () => {
    expect(() => shallow(<FormBlockCollection onChange={jest.fn()} value={undefined} />))
        .toThrow('The "block" field type needs at least one type to be configured!');
});

test('Throw error if empty type array is passed', () => {
    expect(() => shallow(<FormBlockCollection onChange={jest.fn()} value={[]} />))
        .toThrow('The "block" field type needs at least one type to be configured!');
});
