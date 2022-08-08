// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

test('Clicking on a clickable breadcrumb part should call a handler', async() => {
    const clickSpy = jest.fn();
    const testValue = 2;
    render(
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

    const item = screen.queryByText('Crumb 2');
    await userEvent.click(item);

    expect(clickSpy).toHaveBeenCalledWith(testValue);
});
