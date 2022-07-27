// @flow
import {mount, render} from 'enzyme';
import React from 'react';
import Breadcrumb from '../Breadcrumb';

test('Render a Breadcrumb', () => {
    const clickSpy = jest.fn();
    const breadcrumb = render(
        <Breadcrumb onItemClick={clickSpy}>
            <Breadcrumb.Item>
                Crumb 1
            </Breadcrumb.Item>
            <Breadcrumb.Item>
                Crumb 2
            </Breadcrumb.Item>
            <Breadcrumb.Item>
                Crumb 3
            </Breadcrumb.Item>
        </Breadcrumb>
    );
    expect(breadcrumb).toMatchSnapshot();
});

test('Clicking on a clickable breadcrumb part should call a handler', () => {
    const clickSpy = jest.fn();
    const testValue = 2;
    const breadcrumb = mount(
        <Breadcrumb onItemClick={clickSpy}>
            <Breadcrumb.Item>
                Crumb 1
            </Breadcrumb.Item>
            <Breadcrumb.Item value={testValue}>
                Crumb 2
            </Breadcrumb.Item>
            <Breadcrumb.Item>
                Crumb 3
            </Breadcrumb.Item>
        </Breadcrumb>
    );

    breadcrumb.find('Item').at(1).simulate('click');
    expect(clickSpy).toHaveBeenCalledWith(testValue);
});
