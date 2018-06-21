// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import pretty from 'pretty';
import FieldBlocks from '../FieldBlocks';
import FormInspector from '../../Form/FormInspector';
import FormStore from '../../Form/stores/FormStore';
import ResourceStore from '../../../stores/ResourceStore';

jest.mock('../../Form/FormInspector', () => jest.fn());
jest.mock('../../Form/stores/FormStore', () => jest.fn());
jest.mock('../../../stores/ResourceStore', () => jest.fn());

jest.mock('../../Form/registries/FieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'text_line':
                return function TextLine({error, value}) {
                    return <input className={error && error.keyword} type="text" defaultValue={value} />;
                };
        }
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

test('Render block with schema', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

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

    const fieldBlocks = mount(
        <FieldBlocks
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('Block').at(0).simulate('click');
    fieldBlocks.find('Block').at(1).simulate('click');

    expect(pretty(fieldBlocks.html())).toMatchSnapshot();
});

test('Render block with schema and error on fields already being modified', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

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

    const value = [
        {
            text: 'Test1',
        },
        {
            text: 'T2',
        },
        {
            text: 'T3',
        },
    ];

    const error = [
        undefined,
        {
            text: {
                keyword: 'minLength',
                parameters: {},
            },
        },
        {
            text: {
                keyword: 'minLength',
                parameters: {},
            },
        },
    ];

    const fieldBlocks = mount(
        <FieldBlocks
            fieldTypeOptions={{}}
            error={error}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('Block').at(0).simulate('click');
    fieldBlocks.find('Block').at(1).simulate('click');
    fieldBlocks.find('Block').at(2).simulate('click');

    fieldBlocks.find('Block').at(0).find('Field').at(0).prop('onFinish')('text');
    fieldBlocks.find('Block').at(1).find('Field').at(0).prop('onFinish')('text');

    expect(pretty(fieldBlocks.html())).toMatchSnapshot();
});

test('Render block with schema and error on fields already being modified', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

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

    const value = [
        {
            text: 'Test1',
        },
        {
            text: 'T2',
        },
        {
            text: 'T3',
        },
    ];

    const error = [
        undefined,
        {
            text: {
                keyword: 'minLength',
                parameters: {},
            },
        },
        {
            text: {
                keyword: 'minLength',
                parameters: {},
            },
        },
    ];

    const fieldBlocks = mount(
        <FieldBlocks
            error={error}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={true}
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('Block').at(0).simulate('click');
    fieldBlocks.find('Block').at(1).simulate('click');
    fieldBlocks.find('Block').at(2).simulate('click');

    fieldBlocks.find('Block').at(0).find('Field').at(0).prop('onFinish')('text');
    fieldBlocks.find('Block').at(1).find('Field').at(0).prop('onFinish')('text');

    expect(pretty(fieldBlocks.html())).toMatchSnapshot();
});

test('Should correctly pass props to the BlockCollection', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
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
        <FieldBlocks
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={2}
            minOccurs={1}
            onChange={changeSpy}
            onFinish={jest.fn()}
            schemaPath=""
            types={types}
            value={value}
        />
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

test('Should pass correct schemaPath to FieldRender', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    type: 'text_line',
                },
            },
        },
    };

    const fieldBlocks = mount(
        <FieldBlocks
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            schemaPath=""
            types={types}
            value={[{}, {}]}
        />
    );

    fieldBlocks.find('SortableBlocks').prop('onExpand')(0);
    fieldBlocks.find('SortableBlocks').prop('onExpand')(1);
    fieldBlocks.update();

    expect(fieldBlocks.find('FieldRenderer').at(0).prop('schemaPath')).toEqual('/types/default/form');
    expect(fieldBlocks.find('FieldRenderer').at(1).prop('schemaPath')).toEqual('/types/default/form');
});

test('Should call onFinish when a field from the child renderer has finished editing', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

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
    const value = [{}];

    const finishSpy = jest.fn();
    const fieldBlocks = mount(
        <FieldBlocks
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={finishSpy}
            schemaPath=""
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('Block').simulate('click');
    fieldBlocks.find('FieldRenderer').prop('onFieldFinish')();

    expect(finishSpy).toBeCalledWith();
});

test('Should call onFinish when the order of the blocks has changed', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
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
    const value = [{}];

    const finishSpy = jest.fn();
    const fieldBlocks = mount(
        <FieldBlocks
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={finishSpy}
            schemaPath=""
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('BlockCollection').prop('onSortEnd')(0, 2);

    expect(finishSpy).toBeCalledWith();
});

test('Throw error if no types are passed', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    expect(() => shallow(
        <FieldBlocks
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            value={undefined}
        />
    )).toThrow('The "block" field type needs at least one type to be configured!');
});

test('Throw error if empty type array is passed', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    expect(() => shallow(
        <FieldBlocks
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            value={[]}
        />
    )).toThrow('The "block" field type needs at least one type to be configured!');
});
