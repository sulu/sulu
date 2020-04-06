// @flow
import React from 'react';
import {mount} from 'enzyme';
import WebspaceSelect from '../WebspaceSelect';

test('Render WebspaceSelect closed', () => {
    const arrowMenu = mount(
        <WebspaceSelect onChange={jest.fn()} value="sulu">
            <WebspaceSelect.Item value="sulu">Sulu</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_blog">Sulu Blog</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_doc">Sulu Doc</WebspaceSelect.Item>
        </WebspaceSelect>
    );

    expect(arrowMenu.render()).toMatchSnapshot();
});

test('Render WebspaceSelect opened', () => {
    const arrowMenu = mount(
        <WebspaceSelect onChange={jest.fn()} value="sulu">
            <WebspaceSelect.Item value="sulu">Sulu</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_blog">Sulu Blog</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_doc">Sulu Doc</WebspaceSelect.Item>
        </WebspaceSelect>
    );
    expect(arrowMenu.instance().open).toBe(false);

    // click button to open webspace select
    arrowMenu.find('WebspaceSelect button').simulate('click');
    expect(arrowMenu.instance().open).toBe(true);
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
