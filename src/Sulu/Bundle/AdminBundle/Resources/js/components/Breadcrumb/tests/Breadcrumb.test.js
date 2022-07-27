// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import React from 'react';
import Breadcrumb from '../Breadcrumb';

test('Render a Breadcrumb', () => {
    const clickSpy = jest.fn();
    const {container} = render(
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
    expect(container).toMatchSnapshot();
});

test('Clicking on a clickable breadcrumb part should call a handler', () => {
    const clickSpy = jest.fn();
    const testValue = 2;
    const {debug} = render(
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

    debug();

    const item = screen.queryByText('Crumb 2');
    fireEvent.click(item);

    expect(clickSpy).toHaveBeenCalledWith(testValue);
});
