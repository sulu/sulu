/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import Badge from '../../Badge/Badge';
import Tabs from '../Tabs.js';

test('Render a Tabs component', () => {
    const changeSpy = jest.fn();

    expect(render(
        <Tabs onSelect={changeSpy} selectedIndex={null}>
            <Tabs.Tab>
                Tab 1
            </Tabs.Tab>
            <Tabs.Tab>
                Tab 2
            </Tabs.Tab>
            <Tabs.Tab>
                Tab 3
            </Tabs.Tab>
        </Tabs>
    )).toMatchSnapshot();
});

test('Render a Tabs component with a selected tab and a badge', () => {
    const changeSpy = jest.fn();
    const selectedTabIndex = 0;

    expect(render(
        <Tabs onSelect={changeSpy} selectedIndex={selectedTabIndex}>
            <Tabs.Tab>
                Tab 1
            </Tabs.Tab>
            <Tabs.Tab badges={<Badge key="badge1">1</Badge>}>
                Tab 2
            </Tabs.Tab>
            <Tabs.Tab badges={[<Badge key="badge2">2</Badge>, <Badge key="badge3">3</Badge>]}>
                Tab 3
            </Tabs.Tab>
        </Tabs>
    )).toMatchSnapshot();
});

test('Clicking on a Tab should call the onSelect handler', () => {
    const changeSpy = jest.fn();
    const selectedTabIndex = 0;

    const tabs = mount(
        <Tabs onSelect={changeSpy} selectedIndex={null}>
            <Tabs.Tab>
                Tab 1
            </Tabs.Tab>
            <Tabs.Tab>
                Tab 2
            </Tabs.Tab>
            <Tabs.Tab>
                Tab 3
            </Tabs.Tab>
        </Tabs>
    );

    tabs.find('.tab button').at(0).simulate('click');
    expect(changeSpy).toHaveBeenCalledWith(selectedTabIndex);
});
