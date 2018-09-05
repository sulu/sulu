// @flow
import React from 'react';
import {shallow} from 'enzyme';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import TextEditor from '../../fields/TextEditor';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass props correctly to TextEditor', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const textEditor = shallow(
        <TextEditor
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value="xyz"
        />
    );

    expect(textEditor.find('TextEditor').props()).toEqual(expect.objectContaining({
        adapter: 'ckeditor5',
        onBlur: finishSpy,
        onChange: changeSpy,
        value: 'xyz',
    }));
});
