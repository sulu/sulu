// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import pretty from 'pretty';
import FieldBlocks from '../FieldBlocks';

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

    const fieldBlocks = mount(<FieldBlocks onChange={jest.fn()} types={types} value={value} />);

    fieldBlocks.find('Block').at(0).simulate('click');
    fieldBlocks.find('Block').at(1).simulate('click');

    expect(pretty(fieldBlocks.html())).toMatchSnapshot();
});

test('Should correctly pass props to the BlockCollection', () => {
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [];
    const changeSpy = jest.fn();

    const fieldBlocks = shallow(
        <FieldBlocks maxOccurs={2} minOccurs={1} onChange={changeSpy} types={types} value={value} />
    );

    expect(fieldBlocks.find('BlockCollection').props()).toEqual(expect.objectContaining({
        maxOccurs: 2,
        minOccurs: 1,
        onChange: changeSpy,
        types: {
            default: 'Default',
        },
        value,
    }));
});

test('Throw error if no types are passed', () => {
    expect(() => shallow(<FieldBlocks onChange={jest.fn()} value={undefined} />))
        .toThrow('The "block" field type needs at least one type to be configured!');
});

test('Throw error if empty type array is passed', () => {
    expect(() => shallow(<FieldBlocks onChange={jest.fn()} value={[]} />))
        .toThrow('The "block" field type needs at least one type to be configured!');
});
