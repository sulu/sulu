// @flow
import React from 'react';
import {render, waitFor} from '@testing-library/react';
import CKEditor5, {registerCKEditor5Plugins} from '../index';
import configRegistry from '../registries/configRegistry';
import pluginRegistry from '../registries/pluginRegistry';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

// These tests run a real editor instead of a mock, to see what it does with the markup of a text editor config.
const LINE_BREAK_CONFIG = {enterMode: 'br', attributes: ['style'], tags: ['strong']};
const PARAGRAPH_CONFIG = {enterMode: 'p', attributes: ['style'], tags: ['strong']};

const STYLED_PARAGRAPHS = [
    '<p style="text-align:center;">one</p>',
    '<p style="text-align:right;" class="intro">two <span style="color:red;">red</span></p>',
].join('');

window.ResizeObserver = class {
    observe() {}
    unobserve() {}
    disconnect() {}
};

beforeEach(() => {
    pluginRegistry.clear();
    configRegistry.clear();
    registerCKEditor5Plugins(['en']);
});

async function renderEditor(config, value) {
    const changeSpy = jest.fn();
    const ref = React.createRef();

    render(<CKEditor5 config={config} onBlur={jest.fn()} onChange={changeSpy} ref={ref} value={value} />);
    await waitFor(() => expect(ref.current && ref.current.editorInstance).toBeTruthy());

    // $FlowFixMe: the ref is set once the editor exists
    return {changeSpy, editor: ref.current.editorInstance};
}

test('Drop the paragraph and inline styles of pasted content in a line break config', async() => {
    const {changeSpy, editor} = await renderEditor(LINE_BREAK_CONFIG, undefined);

    editor.setData(STYLED_PARAGRAPHS);

    expect(changeSpy).toHaveBeenLastCalledWith('<!--p-->one<!--/p--><br></br><!--p-->two red<!--/p-->');
});

test('Drop the style of a single pasted paragraph in a line break config', async() => {
    const {changeSpy, editor} = await renderEditor(LINE_BREAK_CONFIG, undefined);

    editor.setData('<p style="text-align:center;">one <strong>bold</strong></p>');

    expect(changeSpy).toHaveBeenLastCalledWith('one <strong>bold</strong>');
});

test('Load a stored line break value without paragraph markers or styles', async() => {
    const {changeSpy, editor} = await renderEditor(
        LINE_BREAK_CONFIG,
        '<!--p-->one<!--/p--><br></br><!--p-->two<!--/p-->'
    );

    expect(editor.getData()).toBe('<p>one</p><p>two</p>');
    expect(changeSpy).not.toHaveBeenCalled();
});

test('Load a stored line break value that was never wrapped in a paragraph', async() => {
    const {changeSpy, editor} = await renderEditor(LINE_BREAK_CONFIG, 'one<br>two');

    expect(editor.getData()).toBe('<p>one<br>two</p>');
    expect(changeSpy).not.toHaveBeenCalled();
});

test('Keep the text alignment of a paragraph config', async() => {
    const {changeSpy, editor} = await renderEditor(PARAGRAPH_CONFIG, undefined);

    editor.setData('<p style="text-align:center;">one</p><p>two</p>');

    expect(changeSpy).toHaveBeenLastCalledWith('<p style="text-align:center;">one</p><p>two</p>');
});
