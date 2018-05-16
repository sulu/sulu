// @flow
import React from 'react';
import {shallow, render} from 'enzyme';
import Button from '../Button';

test('Render with downloadUrl', () => {
    expect(render(<Button downloadUrl="/test.jpg" icon="su-download">Download</Button>)).toMatchSnapshot();
});

test('Render with onClick', () => {
    expect(render(<Button onClick={jest.fn()} icon="su-download">Download</Button>)).toMatchSnapshot();
});

test('Call onClick handler when clicked', () => {
    const clickSpy = jest.fn();

    const button = shallow(<Button onClick={clickSpy} icon="su-trash-alt">Delete</Button>);

    button.simulate('click');

    expect(clickSpy).toBeCalledWith();
});
