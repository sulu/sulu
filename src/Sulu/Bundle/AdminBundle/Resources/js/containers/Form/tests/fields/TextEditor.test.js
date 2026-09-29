// @flow
import React from 'react';
import {observable} from 'mobx';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import TextEditor from '../../fields/TextEditor';
import userStore from '../../../../stores/userStore';

let mockTextEditorProps: Object = {};

const mockReact = require('react');

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../stores/userStore', () => ({}));
jest.mock('../../../TextEditor', () => jest.fn((props) => {
    mockTextEditorProps = props;

    return mockReact.createElement('div');
}));

beforeEach(() => {
    mockTextEditorProps = {};
});

test('Pass props correctly to TextEditor', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const options = {};

    const locale = observable.box('en');
    // $FlowFixMe
    formInspector.locale = locale;

    render(
        <TextEditor
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={options}
            value="xyz"
        />
    );

    expect(mockTextEditorProps).toEqual(expect.objectContaining({
        adapter: 'ckeditor5',
        locale,
        onBlur: finishSpy,
        onChange: changeSpy,
        options,
        value: 'xyz',
        disabled: true,
    }));
});

test('Pass content locale from user to TextEditor if form has no locale', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const options = {};

    // $FlowFixMe
    userStore.contentLocale = 'de';

    render(
        <TextEditor
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={options}
            value="xyz"
        />
    );

    expect(mockTextEditorProps.locale).toBeDefined();
    expect(mockTextEditorProps.locale.get()).toEqual('de');
});

test('Call onFocus when editor get focus', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const focusSpy = jest.fn();

    render(
        <TextEditor
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFocus={focusSpy}
        />
    );

    const target = new EventTarget();

    mockTextEditorProps.onFocus({target});

    expect(focusSpy).toHaveBeenCalledWith(target);
});
