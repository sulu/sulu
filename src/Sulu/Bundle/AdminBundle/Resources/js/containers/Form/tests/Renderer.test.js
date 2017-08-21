/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Renderer from '../Renderer';

test('Should render a form tag', () => {
    const renderer = render(<Renderer />);
    expect(renderer).toMatchSnapshot();
});

test('Should prevent default submit handling', () => {
    const preventDefaultSpy = jest.fn();
    const submitSpy = jest.fn();
    const renderer = shallow(<Renderer onSubmit={submitSpy} />);

    renderer.find('form').simulate('submit', {preventDefault: preventDefaultSpy});
    expect(preventDefaultSpy).toBeCalled();
});

test('Should call onSubmit callback when submitted', () => {
    const submitSpy = jest.fn();
    const renderer = mount(<Renderer onSubmit={submitSpy} />);

    renderer.instance().submit();
    expect(submitSpy).toBeCalled();
});
