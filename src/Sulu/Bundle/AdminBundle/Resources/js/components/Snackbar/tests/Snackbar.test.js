// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

test('Render a info snackbar', () => {
    const {container} = render(
        <Snackbar message="Something unimportant went wrong" onCloseClick={jest.fn()} type="info" />
    );

    expect(container).toMatchSnapshot();
});

test('Render a success snackbar', () => {
    const {container} = render(
        <Snackbar message="Something unimportant went wrong" onCloseClick={jest.fn()} type="success" />
    );

    expect(container).toMatchSnapshot();
});

test('Render a floating snackbar', () => {
    const {container} = render(
        <Snackbar
            icon="su-copy"
            message="3 blocks copied to clipboard"
            onCloseClick={jest.fn()}
            skin="floating"
            type="info"
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render an error snackbar without close button', () => {
    const {container} = render(<Snackbar message="Something went wrong" type="error" />);
    expect(container).toMatchSnapshot();
});

test('Click the snackbar should call the onClick callback', async() => {
    const clickSpy = jest.fn();
    render(<Snackbar message="Something went wrong" onClick={clickSpy} type="error" />);

    const snackbar = screen.queryByText('- Something went wrong');
    await userEvent.click(snackbar);

    expect(clickSpy).toHaveBeenCalled();
});

test('Render a snackbar with a title and an action', () => {
    const {container} = render(
        <Snackbar
            actions={[{label: 'Try Again', onClick: jest.fn()}]}
            message="Something went wrong"
            title="Out of Credits"
            type="error"
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render multiple actions side by side', () => {
    const {container} = render(
        <Snackbar
            actions={[
                {label: 'Try Again', onClick: jest.fn()},
                {label: 'Contact Admin', onClick: jest.fn()},
            ]}
            message="Something went wrong"
            type="warning"
        />
    );

    expect(screen.getByText('Try Again')).toBeInTheDocument();
    expect(screen.getByText('Contact Admin')).toBeInTheDocument();
    expect(container).toMatchSnapshot();
});

test('Render the type as title when no title is given', () => {
    render(<Snackbar message="Something went wrong" type="warning" />);

    expect(screen.getByText('sulu_admin.warning')).toBeInTheDocument();
});

test('Call the action callback without triggering onClick when the action is clicked', async() => {
    const actionClickSpy = jest.fn();
    const clickSpy = jest.fn();
    render(
        <Snackbar
            actions={[{label: 'Try Again', onClick: actionClickSpy}]}
            message="Something went wrong"
            onClick={clickSpy}
            type="warning"
        />
    );

    await userEvent.click(screen.queryByText('Try Again'));

    expect(actionClickSpy).toHaveBeenCalled();
    expect(clickSpy).not.toHaveBeenCalled();
});

test('Call onCloseClick callback when close button is clicked', async() => {
    const closeClickSpy = jest.fn();
    render(<Snackbar message="Something went wrong" onCloseClick={closeClickSpy} type="error" />);

    const icon = screen.queryByLabelText('su-times');
    await userEvent.click(icon);

    expect(closeClickSpy).toHaveBeenCalledWith();
});
