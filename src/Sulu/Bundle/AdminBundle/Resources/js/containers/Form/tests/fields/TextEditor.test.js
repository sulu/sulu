// @flow
import React from 'react';
import {observable} from 'mobx';
import {act, render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import TextEditor from '../../fields/TextEditor';
import TextEditorContainer from '../../../../containers/TextEditor';
import userStore from '../../../../stores/userStore';

jest.mock('../../../../containers/TextEditor', () => jest.fn(() => null));
jest.mock('../../../../stores/userStore', () => ({}));

function getLatestTextEditorContainerProps() {
    const calls = (TextEditorContainer: any).mock.calls;
    return calls[calls.length - 1][0];
}

test('Pass props correctly to TextEditor', () => {
    const formInspector = ({locale: observable.box('en')}: any);
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const options = {};

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

    expect(getLatestTextEditorContainerProps()).toEqual(expect.objectContaining({
        adapter: 'ckeditor5',
        locale: formInspector.locale,
        onBlur: finishSpy,
        onChange: changeSpy,
        options,
        value: 'xyz',
        disabled: true,
    }));
});

test('Pass content locale from user to TextEditor if form has no locale', () => {
    const formInspector = ({locale: undefined}: any);
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
    const textEditorProps = getLatestTextEditorContainerProps();

    expect(textEditorProps.locale).toBeDefined();
    expect(textEditorProps.locale.get()).toEqual('de');
});

test('Call onFocus when editor get focus', () => {
    const formInspector = ({locale: undefined}: any);
    const focusSpy = jest.fn();

    render(
        <TextEditor
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFocus={focusSpy}
        />
    );

    const target = new EventTarget();

    act(() => {
        getLatestTextEditorContainerProps().onFocus({target});
    });

    expect(focusSpy).toBeCalledWith(target);
});
