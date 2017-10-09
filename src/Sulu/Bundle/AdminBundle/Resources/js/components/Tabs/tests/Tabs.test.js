/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import Tabs from '../Tabs.js';

test('Render a Tabs component', () => {
    const changeSpy = jest.fn();

    expect(render(
        <Tabs value={null} onChange={changeSpy}>
            <Tabs.Tab label="Tab 1" value="1">
                Hello 1
            </Tabs.Tab>
            <Tabs.Tab label="Tab 2" value="2">
                Hello 2
            </Tabs.Tab>
            <Tabs.Tab label="Tab 3" value="3">
                Hello 3
            </Tabs.Tab>
        </Tabs>
    )).toMatchSnapshot();
});

test('Render a Tabs component with a selected tab', () => {
    const changeSpy = jest.fn();
    const selectedTabValue = 1;

    expect(render(
        <Tabs value={selectedTabValue} onChange={changeSpy}>
            <Tabs.Tab label="Tab 1" value={selectedTabValue}>
                Hello 1
            </Tabs.Tab>
            <Tabs.Tab label="Tab 2" value="2">
                Hello 2
            </Tabs.Tab>
            <Tabs.Tab label="Tab 3" value="3">
                Hello 3
            </Tabs.Tab>
        </Tabs>
    )).toMatchSnapshot();
});

test('Clicking on a Tab should call the onChange handler', () => {
    const changeSpy = jest.fn();
    const selectedTabValue = 1;

    const tabs = mount(
        <Tabs value={null} onChange={changeSpy}>
            <Tabs.Tab label="Tab 1" value={selectedTabValue}>
                Hello 1
            </Tabs.Tab>
            <Tabs.Tab label="Tab 2" value="2">
                Hello 2
            </Tabs.Tab>
            <Tabs.Tab label="Tab 3" value="3">
                Hello 3
            </Tabs.Tab>
        </Tabs>
    );

    tabs.find('.tab button').at(0).simulate('click');
    expect(changeSpy).toHaveBeenCalledWith(selectedTabValue);
});
