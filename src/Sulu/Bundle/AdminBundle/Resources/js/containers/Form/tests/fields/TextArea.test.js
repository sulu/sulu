// @flow
import React from 'react';
import log from 'loglevel';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import TextArea from '../../fields/TextArea';
import TextAreaComponent from '../../../../components/TextArea';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error correctly to TextArea component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const error = {keyword: 'minLength', parameters: {}};

    const inputInvalid = shallow(
        <TextArea
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
            value="xyz"
        />
    );

    expect(inputInvalid.find(TextAreaComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to TextArea component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const inputValid = shallow(
        <TextArea
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(inputValid.find(TextAreaComponent).prop('maxCharacters')).toBe(undefined);
    expect(inputValid.find(TextAreaComponent).prop('valid')).toBe(true);
    expect(inputValid.find(TextAreaComponent).prop('disabled')).toBe(true);
});

test('Component correctly logs deprecated warning for max_characters', () => {
    const schemaOptions = {
        max_characters: {
            name: 'max_characters',
            value: '70',
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const inputValid = shallow(
        <TextArea
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(log.warn).toBeCalledWith(expect.stringContaining('The "max_characters" schema option is deprecated'));

    expect(inputValid.find(TextAreaComponent).prop('maxCharacters')).toBe(70);
    expect(inputValid.find(TextAreaComponent).prop('valid')).toBe(true);
});

test('Component correctly chooses softMaxLength over max_characters', () => {
    const schemaOptions = {
        max_characters: {
            name: 'max_characters',
            value: '55',
        },
        softMaxLength: {
            name: 'softMaxLength',
            value: '70',
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const inputValid = shallow(
        <TextArea
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(log.warn).toBeCalledWith(expect.stringContaining('The "max_characters" schema option is deprecated'));

    expect(inputValid.find(TextAreaComponent).prop('maxCharacters')).toBe(70);
    expect(inputValid.find(TextAreaComponent).prop('valid')).toBe(true);
});

test('Pass props correctly including softMaxLength to TextArea component', () => {
    const schemaOptions = {
        softMaxLength: {
            name: 'softMaxLength',
            value: '70',
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const inputValid = shallow(
        <TextArea
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(inputValid.find(TextAreaComponent).prop('maxCharacters')).toBe(70);
    expect(inputValid.find(TextAreaComponent).prop('valid')).toBe(true);
});
