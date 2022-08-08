// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import log from 'loglevel';
import Url from '../Url';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

test('Render the component as disabled', () => {
    const {container} = render(
        <Url disabled={true} onChange={jest.fn()} protocols={['http://', 'https://']} value={undefined} />
    );
    expect(container).toMatchSnapshot();
});

test('Render the component with an error', () => {
    const {container} = render(
        <Url
            defaultProtocol="http://"
            onChange={jest.fn()}
            protocols={['http://', 'https://']}
            valid={false}
            value={undefined}
        />
    );
    expect(container).toMatchSnapshot();
});

test('Set the correct values for protocol and path when initializing', () => {
    render(<Url onChange={jest.fn()} value="http://www.sulu.io" />);

    const protocol = screen.queryByTitle('http://').lastChild;
    const input = screen.queryByRole('textbox');

    expect(input).toHaveValue('www.sulu.io');
    expect(protocol).toHaveTextContent('http://');
});

test('Set the correct values for protocol and path when updating', () => {
    render(<Url onChange={jest.fn()} value="https://www.sulu.io" />);

    const protocol = screen.queryByTitle('https://').lastChild;
    const input = screen.queryByRole('textbox');

    expect(input).toHaveValue('www.sulu.io');
    expect(protocol).toHaveTextContent('https://');
});

test('Should log a warning if a not available protocol has been given', () => {
    render(<Url onChange={jest.fn()} protocols={['http://']} value="https://www.sulu.io" />);

    const input = screen.queryByRole('textbox');

    expect(input).toHaveValue('https://www.sulu.io');
    expect(log.warn).toBeCalled();
});

test('Show error when invalid email was passed via updated prop', () => {
    const {container, rerender} = render(<Url onChange={jest.fn()} value={undefined} />);

    expect(container.children[0]).not.toHaveClass('error');
    rerender(<Url onChange={jest.fn()} value="mailto:invalid-email" />);
    expect(container.children[0]).toHaveClass('error');
});

test('Should not reset value of protocol select when undefined value is passed', () => {
    const {rerender} = render(<Url onChange={jest.fn()} value="https://" />);

    expect(screen.queryByTitle('https://').lastChild).toHaveTextContent('https://');
    expect(screen.queryByRole('textbox')).toHaveValue('');
    rerender(<Url onChange={jest.fn()} value={undefined} />);
    expect(screen.queryByTitle('https://').lastChild).toHaveTextContent('https://');
    expect(screen.queryByRole('textbox')).toHaveValue('');
});

test('Remove error when valid email was passed via updated prop', () => {
    const {container, rerender} = render(<Url onChange={jest.fn()} value="mailto:invalid-email" />);

    expect(container.children[0]).toHaveClass('error');
    rerender(<Url onChange={jest.fn()} value="mailto:hello@sulu.io" />);
    expect(container.children[0]).not.toHaveClass('error');
});

test('Remove error when valid email was changed using the text field', async() => {
    const {container, rerender} = render(<Url onChange={jest.fn()} value="mailto:invalid-email" />);

    expect(container.children[0]).toHaveClass('error');

    rerender(<Url onChange={jest.fn()} value="hello@sulu.io" />);

    expect(container.children[0]).not.toHaveClass('error');
});

test('Call onChange callback with the first protocol if none was selected', async() => {
    const changeSpy = jest.fn();
    render(<Url onChange={changeSpy} value="sulu.a" />);

    const input = screen.queryByRole('textbox');
    await userEvent.type(input, 't');

    expect(changeSpy).toBeCalledWith('http://sulu.at');
});

test('Call onChange callback when protocol was changed', async() => {
    const changeSpy = jest.fn();
    render(<Url onChange={changeSpy} value="https://www.sulu.io" />);

    await userEvent.click(screen.queryByLabelText('su-angle-down'));
    await userEvent.click(screen.queryByText('http://'));

    expect(changeSpy).toBeCalledWith('http://www.sulu.io');
});

test('Call onChange callback when path was changed', async() => {
    const changeSpy = jest.fn();
    render(<Url onChange={changeSpy} value="https://www.sulu.io" />);

    const input = screen.queryByRole('textbox');
    await userEvent.type(input, 'x');

    expect(changeSpy).toBeCalledWith('https://www.sulu.iox');
});

test('Call onChange callback when path was changed but not blurred', async() => {
    const changeSpy = jest.fn();
    render(<Url onChange={changeSpy} value="https://www.sulu.io" />);

    const input = screen.queryByRole('textbox');
    await userEvent.type(input, 'x');

    expect(changeSpy).toBeCalledWith('https://www.sulu.iox');
});

test('Call onChange callback when path was changed to invalid url but not blurred', async() => {
    const changeSpy = jest.fn();
    render(<Url onChange={changeSpy} value="https://www.sulu.io" />);

    const input = screen.queryByRole('textbox');
    await userEvent.type(input, '[Backspace]');

    expect(changeSpy).toBeCalledWith('https://www.sulu.i');
});

test('Call onChange callback if url is not valid but leave the current value', async() => {
    const changeSpy = jest.fn();
    const {container} = render(<Url onChange={changeSpy} value="https://www.sulu.io" />);

    const input = screen.queryByRole('textbox');
    const protocol = screen.queryByTitle('https://').lastChild;
    await userEvent.type(input, '.');

    expect(changeSpy).toBeCalledWith('https://www.sulu.io.');
    expect(protocol).toHaveTextContent('https://');
    expect(input).toHaveValue('www.sulu.io.');
    expect(container.children[0]).not.toHaveClass('error');
});

test('Call onChange callback with undefined if email is not valid but leave the current value', async() => {
    const changeSpy = jest.fn();
    const {container} = render(<Url onChange={changeSpy} value="mailto:hello@sulu.io" />);

    const input = screen.queryByRole('textbox');
    const protocol = screen.queryByTitle('mailto:').lastChild;
    await userEvent.type(input, '@');

    expect(changeSpy).toBeCalledWith(undefined);
    expect(protocol).toHaveTextContent('mailto:');
    expect(input).toHaveValue('hello@sulu.io@');

    await userEvent.tab();
    expect(container.children[0]).toHaveClass('error');
});

test('Call onChange callback with correct mail address', async() => {
    const changeSpy = jest.fn();
    const {container} = render(<Url onChange={changeSpy} protocols={['mailto:']} value="test@example." />);

    const input = screen.queryByRole('textbox');
    const protocol = screen.queryByTitle('mailto:').lastChild;
    await userEvent.type(input, 'a');

    expect(changeSpy).toBeCalledWith('mailto:test@example.a');
    expect(protocol).toHaveTextContent('mailto:');
    expect(input).toHaveValue('test@example.a');

    await userEvent.tab();
    expect(container.children[0]).not.toHaveClass('error');
});

test('Call onChange callback with correct value with custom protocol', async() => {
    const changeSpy = jest.fn();
    const {container} = render(<Url onChange={changeSpy} protocols={['custom-protocol:']} value={undefined} />);

    const input = screen.queryByRole('textbox');
    const protocol = screen.queryByTitle('custom-protocol:').lastChild;
    await userEvent.type(input, 'X');

    expect(changeSpy).toBeCalledWith('custom-protocol:X');
    expect(protocol).toHaveTextContent('custom-protocol:');
    expect(input).toHaveValue('X');
    expect(container.children[0]).not.toHaveClass('error');
});

test('Call onChange callback with undefined if incorrect mail address is entered', async() => {
    const changeSpy = jest.fn();
    const {container} = render(<Url onChange={changeSpy} protocols={['mailto:']} value={undefined} />);

    const input = screen.queryByRole('textbox');
    const protocol = screen.queryByTitle('mailto:').lastChild;
    await userEvent.type(input, 'X');

    expect(protocol).toHaveTextContent('mailto');
    expect(input).toHaveValue('X');
    expect(changeSpy).toBeCalledWith(undefined);

    await userEvent.tab();
    expect(container.children[0]).toHaveClass('error');
});

test('Should remove the protocol from path and set it on the protocol select', async() => {
    const changeSpy = jest.fn();
    render(<Url onChange={changeSpy} value="http://www.sulu.a" />);

    const input = screen.queryByRole('textbox');
    const protocol = screen.queryByTitle('http://').lastChild;
    await userEvent.type(input, 't');

    expect(protocol).toHaveTextContent('http://');
    expect(input).toHaveValue('www.sulu.at');
});

test(
    'Should remove the protocol from path and set it on the protocol select if protocol is already selected',
    async() => {
        const changeSpy = jest.fn();
        render(<Url onChange={changeSpy} value="http://www.sulu.a" />);

        const input = screen.queryByRole('textbox');
        const protocol = screen.queryByTitle('http://').lastChild;
        await userEvent.type(input, 't');

        expect(protocol).toHaveTextContent('http://');
        expect(input).toHaveValue('www.sulu.at');
    });

test('Call onBlur callback when protocol was changed', async() => {
    const blurSpy = jest.fn();
    render(<Url onBlur={blurSpy} onChange={jest.fn()} value="https://www.sulu.io" />);

    await userEvent.click(screen.queryByLabelText('su-angle-down'));
    await userEvent.click(screen.queryByText('http://'));

    expect(blurSpy).toBeCalledWith();
});

test('Call onBlur callback when path was changed', async() => {
    const blurSpy = jest.fn();
    render(<Url onBlur={blurSpy} onChange={jest.fn()} value="https://www.sulu.io" />);

    const input = screen.queryByRole('textbox');
    await userEvent.click(input);
    expect(blurSpy).not.toBeCalledWith();

    await userEvent.tab();
    expect(blurSpy).toBeCalledWith();
});

test('Should call onProtocolChange with default protocol', () => {
    const protocolChangeSpy = jest.fn();
    render(
        <Url defaultProtocol="http://" onChange={jest.fn()} onProtocolChange={protocolChangeSpy} value={undefined} />
    );

    expect(protocolChangeSpy).toBeCalledWith('http://');
});

test('Should call onProtocolChange with initial value', () => {
    const protocolChangeSpy = jest.fn();
    render(<Url onChange={jest.fn()} onProtocolChange={protocolChangeSpy} value="http://www.google.at" />);

    expect(protocolChangeSpy).toBeCalledWith('http://');
});

test('Should call onProtocolChange when protocol is changed', async() => {
    const changeSpy = jest.fn();
    const protocolChangeSpy = jest.fn();
    render(<Url onChange={changeSpy} onProtocolChange={protocolChangeSpy} value={undefined} />);

    await userEvent.click(screen.queryByLabelText('su-angle-down'));
    await userEvent.click(screen.queryByText('https://'));

    expect(protocolChangeSpy).toHaveBeenLastCalledWith('https://');
});
