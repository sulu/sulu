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

test('Pass props correctly to Input component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const textEditor = shallow(
        <TextEditor
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

    expect(textEditor.find('TextEditor').props()).toEqual(expect.objectContaining({
        adapter: 'ckeditor5',
        value: 'xyz',
    }));
});
