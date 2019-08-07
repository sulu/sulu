/* eslint-disable flowtype/require-valid-file-annotation */
import {render, mount, shallow} from 'enzyme';
import React from 'react';
import Option from '../Option';

jest.mock('../../../utils/DOM/afterElementsRendered');

test('The component should render', () => {
    const option = render(<Option value="my-option">My option</Option>);
    expect(option).toMatchSnapshot();
});

test('The component should render in selected state', () => {
    const option = render(<Option selected={true} value="my-option">My option</Option>);
    expect(option).toMatchSnapshot();
});

test('The component should render with checkbox', () => {
    const option = render(<Option selectedVisualization="checkbox" value="my-option">My option</Option>);
    expect(option).toMatchSnapshot();
});

test('The component should render in disabled state', () => {
    const option = render(<Option disabled={true} value="my-option">My option</Option>);
    expect(option).toMatchSnapshot();
});

test('A click on the component should fire the callback', () => {
    const clickSpy = jest.fn();
    const option = shallow(<Option onClick={clickSpy}>My option</Option>);
    option.find('button').simulate('click');
    expect(clickSpy).toBeCalled();
});

test('The component should be focused when the corresponding property was set', (done) => {
    expect(document.activeElement.tagName).toBe('BODY');
    mount(<Option focus={true}>My option</Option>);
    setTimeout(() => {
        expect(document.activeElement.tagName).toBe('BUTTON');
        done();
    });
});
