/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import Breadcrumb from '../Breadcrumb';

test('Render a Breadcrumb', () => {
    const clickSpy1 = jest.fn();
    const clickSpy2 = jest.fn();
    const breadcrumb = render(
        <Breadcrumb>
            <Breadcrumb.Crumb onClick={clickSpy1}>
                Crumb 1
            </Breadcrumb.Crumb>
            <Breadcrumb.Crumb onClick={clickSpy2}>
                Crumb 2
            </Breadcrumb.Crumb>
            <Breadcrumb.Crumb>
                Crumb 3
            </Breadcrumb.Crumb>
        </Breadcrumb>
    );
    expect(breadcrumb).toMatchSnapshot();
});

test('Clicking on a clickable breadcrumb part should call a handler', () => {
    const clickSpy1 = jest.fn();
    const clickSpy2 = jest.fn();
    const testValue = 2;
    const breadcrumb = mount(
        <Breadcrumb>
            <Breadcrumb.Crumb onClick={clickSpy1}>
                Crumb 1
            </Breadcrumb.Crumb>
            <Breadcrumb.Crumb value={testValue} onClick={clickSpy2}>
                Crumb 2
            </Breadcrumb.Crumb>
            <Breadcrumb.Crumb>
                Crumb 3
            </Breadcrumb.Crumb>
        </Breadcrumb>
    );

    breadcrumb.find('Crumb').at(0).simulate('click');
    expect(clickSpy1).toBeCalled();

    breadcrumb.find('Crumb').at(1).simulate('click');
    expect(clickSpy2).toHaveBeenCalledWith(testValue);
});
