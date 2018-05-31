// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import Snackbar from '../Snackbar';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render a snackbar', () => {
    expect(render(<Snackbar onCloseClick={jest.fn()} />)).toMatchSnapshot();
});

test('Call onCloseClick callback when close button is clicked', () => {
    const closeClickSpy = jest.fn();
    const snackbar = shallow(<Snackbar onCloseClick={closeClickSpy} />);

    snackbar.find('button').simulate('click');

    expect(closeClickSpy).toBeCalledWith();
});
