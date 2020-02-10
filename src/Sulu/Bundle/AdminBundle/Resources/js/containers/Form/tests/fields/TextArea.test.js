// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import TextArea from '../../fields/TextArea';
import TextAreaComponent from '../../../../components/TextArea';

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

test('Pass props correctly including max_characters to TextArea component', () => {
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

    expect(inputValid.find(TextAreaComponent).prop('maxCharacters')).toBe(70);
    expect(inputValid.find(TextAreaComponent).prop('valid')).toBe(true);
});
