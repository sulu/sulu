// @flow
import React from 'react';
import {observable} from 'mobx';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import getLatestMockProps from '../../../../utils/TestHelper/getLatestMockProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import TextEditor from '../../fields/TextEditor';
import userStore from '../../../../stores/userStore';
import TextEditorContainer from '../../../../containers/TextEditor';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../stores/userStore', () => ({}));
jest.mock('../../../../containers/TextEditor', () => jest.fn(() => null));

beforeEach(() => {
    ((TextEditorContainer: any): {mockClear: () => void}).mockClear();
});

test('Pass props correctly to TextEditor', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const options = {};

    const locale = observable.box('en');
    (formInspector: any).locale = locale;

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

    const textEditorProps: any = getLatestMockProps((TextEditorContainer: any));
    expect(textEditorProps).toEqual(expect.objectContaining({
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

    ((userStore: any): {[string]: any}).contentLocale = 'de';

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

    const textEditorProps: any = getLatestMockProps((TextEditorContainer: any));
    expect(textEditorProps.locale).toBeDefined();
    expect(textEditorProps.locale.get()).toEqual('de');
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

    const textEditorProps: any = getLatestMockProps((TextEditorContainer: any));
    textEditorProps.onFocus({target});

    expect(focusSpy).toBeCalledWith(target);
});
