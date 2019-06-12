// @flow
import React from 'react';
import {mount} from 'enzyme';
import pretty from 'pretty';
import WebspaceSelect from '../WebspaceSelect';

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('Render WebspaceSelect closed', () => {
    const handleChange = jest.fn();
    const value = 'sulu';

    const arrowMenu = mount(
        <WebspaceSelect onChange={handleChange} value={value}>
            <WebspaceSelect.Item value="sulu">Sulu</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_blog">Sulu Blog</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_doc">Sulu Doc</WebspaceSelect.Item>
        </WebspaceSelect>
    );

    expect(arrowMenu.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();
});

test('Render WebspaceSelect opened', () => {
    const handleChange = jest.fn();
    const value = 'sulu';

    const arrowMenu = mount(
        <WebspaceSelect onChange={handleChange} value={value}>
            <WebspaceSelect.Item value="sulu">Sulu</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_blog">Sulu Blog</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_doc">Sulu Doc</WebspaceSelect.Item>
        </WebspaceSelect>
    );
    expect(arrowMenu.instance().open).toBe(false);

    // click button to open webspace select
    arrowMenu.find('WebspaceSelect button').simulate('click');
    expect(arrowMenu.instance().open).toBe(true);

    expect(arrowMenu.render()).toMatchSnapshot();
});

test('Change event should be called correctly', () => {
    const handleChange = jest.fn();
    const value = 'sulu';

    const webspaceSelect = mount(
        <WebspaceSelect onChange={handleChange} value={value}>
            <WebspaceSelect.Item value="sulu">Sulu</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_blog">Sulu Blog</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_doc">Sulu Doc</WebspaceSelect.Item>
        </WebspaceSelect>
    );
    expect(webspaceSelect.instance().open).toBe(false);

    // click button to open webspace select
    webspaceSelect.find('WebspaceSelect button').simulate('click');
    expect(webspaceSelect.instance().open).toBe(true);

    // click second item to fire change event
    webspaceSelect.find('Item').at(1).simulate('click');
    expect(handleChange).toBeCalledWith('sulu_blog');
});
