// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import ErrorSnackbar from '../ErrorSnackbar';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render an invisible error-snackbar', () => {
    expect(render(<ErrorSnackbar visible={false} />)).toMatchSnapshot();
});

test('Render an error-snackbar', () => {
    expect(render(<ErrorSnackbar onCloseClick={jest.fn()} />)).toMatchSnapshot();
});

test('Render an error-snackbar without close button', () => {
    expect(render(<ErrorSnackbar />)).toMatchSnapshot();
});

test('Call onCloseClick callback when close button is clicked', () => {
    const closeClickSpy = jest.fn();
    const snackbar = shallow(<ErrorSnackbar onCloseClick={closeClickSpy} />);

    snackbar.find('button').simulate('click');

    expect(closeClickSpy).toBeCalledWith();
});
