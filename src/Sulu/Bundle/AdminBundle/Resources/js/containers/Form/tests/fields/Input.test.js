// @flow
import React from 'react';
import {shallow} from 'enzyme';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import Input from '../../fields/Input';
import InputComponent from '../../../../components/Input';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error correctly to Input component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const error = {keyword: 'minLength', parameters: {}};

    const inputInvalid = shallow(
        <Input
            dataPath=""
            error={error}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value="xyz"
        />
    );

    expect(inputInvalid.find(InputComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to Input component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const inputValid = shallow(
        <Input
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value="xyz"
        />
    );

    expect(inputValid.find(InputComponent).prop('maxCharacters')).toBe(undefined);
    expect(inputValid.find(InputComponent).prop('valid')).toBe(true);
});

test('Pass props correctly including maxCharacters to Input component', () => {
    const schemaOptions = {
        max_characters: {
            value: '70',
        },
    };
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const inputValid = shallow(
        <Input
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value="xyz"
        />
    );

    expect(inputValid.find(InputComponent).prop('maxCharacters')).toBe(70);
    expect(inputValid.find(InputComponent).prop('valid')).toBe(true);
});

test('Should not pass any arguments to onFinish callback', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const finishSpy = jest.fn();

    const input = shallow(
        <Input
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={finishSpy}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value="xyz"
        />
    );

    input.find('Input').prop('onBlur')('Test');

    expect(finishSpy).toBeCalledWith();
});
