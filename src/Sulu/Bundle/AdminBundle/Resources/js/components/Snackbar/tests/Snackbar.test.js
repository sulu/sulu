// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import Snackbar from '../Snackbar';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render an error snackbar', () => {
    const {container} = render(<Snackbar message="Something went wrong" onCloseClick={jest.fn()} type="error" />);
    expect(container).toMatchSnapshot();
});

test('Render an updated error snackbar', () => {
    const {container, rerender} = render(<Snackbar
        message="Something went wrong"
        onCloseClick={jest.fn()}
        type="error"
    />);
    rerender(<Snackbar message="Something went wrong again" onCloseClick={jest.fn()} type="error" />);
    expect(container).toMatchSnapshot();
});

test('Render a warning snackbar', () => {
    const {container} = render(
        <Snackbar message="Something unimportant went wrong" onCloseClick={jest.fn()} type="warning" />
    );

    expect(container).toMatchSnapshot();
});

test('Render an error snackbar without close button', () => {
    const {container} = render(<Snackbar message="Something went wrong" type="error" />);
    expect(container).toMatchSnapshot();
});

test('Click the snackbar should call the onClick callback', () => {
    const clickSpy = jest.fn();
    render(<Snackbar message="Something went wrong" onClick={clickSpy} type="error" />);

    const snackbar = screen.queryByText('- Something went wrong');
    fireEvent.click(snackbar);

    expect(clickSpy).toBeCalled();
});

test('Call onCloseClick callback when close button is clicked', () => {
    const closeClickSpy = jest.fn();
    render(<Snackbar message="Something went wrong" onCloseClick={closeClickSpy} type="error" />);

    const icon = screen.queryByLabelText('su-times');
    fireEvent.click(icon);

    expect(closeClickSpy).toBeCalledWith();
});
