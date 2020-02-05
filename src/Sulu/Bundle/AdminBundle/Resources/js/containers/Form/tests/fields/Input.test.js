// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Input from '../../fields/Input';
import InputComponent from '../../../../components/Input';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error correctly to Input component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const error = {keyword: 'minLength', parameters: {}};

    const inputInvalid = shallow(
        <Input
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(inputInvalid.find(InputComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to Input component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const inputValid = shallow(
        <Input
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(inputValid.find(InputComponent).prop('maxCharacters')).toBe(undefined);
    expect(inputValid.find(InputComponent).prop('valid')).toBe(true);
    expect(inputValid.find(InputComponent).prop('disabled')).toBe(true);
    expect(inputValid.find(InputComponent).prop('headline')).toBe(undefined);
});

test('Pass headline prop correctly', () => {
    const schemaOptions = {
        headline: {
            name: 'headline',
            value: true,
        },
    };
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const inputValid = shallow(
        <Input
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(inputValid.find(InputComponent).prop('headline')).toBe(true);
});

test('Pass props correctly including maxCharacters to Input component', () => {
    const schemaOptions = {
        max_characters: {
            name: 'max_characters',
            value: '70',
        },
        max_segments: {
            name: 'max_segments',
            value: '6',
        },
        segment_delimiter: {
            name: 'segment_delimiter',
            value: ',',
        },
    };
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const inputValid = shallow(
        <Input
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(inputValid.find(InputComponent).prop('maxCharacters')).toBe(70);
    expect(inputValid.find(InputComponent).prop('maxSegments')).toBe(6);
    expect(inputValid.find(InputComponent).prop('segmentDelimiter')).toBe(',');
    expect(inputValid.find(InputComponent).prop('valid')).toBe(true);
});

test('Should not pass any arguments to onFinish callback', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const finishSpy = jest.fn();

    const input = shallow(
        <Input
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFinish={finishSpy}
        />
    );

    input.find('Input').prop('onBlur')('Test');

    expect(finishSpy).toBeCalledWith();
});
