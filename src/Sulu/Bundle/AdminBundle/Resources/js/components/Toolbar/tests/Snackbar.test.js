// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import Snackbar from '../Snackbar';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render a success snackbar', () => {
    expect(render(<Snackbar onCloseClick={jest.fn()} type="success" />)).toMatchSnapshot();
});

test('Render an error snackbar', () => {
    expect(render(<Snackbar onCloseClick={jest.fn()} type="error" />)).toMatchSnapshot();
});

test('Render an error snackbar without close button', () => {
    expect(render(<Snackbar type="error" />)).toMatchSnapshot();
});

test('Call onCloseClick callback when close button is clicked', () => {
    const closeClickSpy = jest.fn();
    const snackbar = shallow(<Snackbar onCloseClick={closeClickSpy} type="error" />);

    snackbar.find('button').simulate('click');

    expect(closeClickSpy).toBeCalledWith();
});
