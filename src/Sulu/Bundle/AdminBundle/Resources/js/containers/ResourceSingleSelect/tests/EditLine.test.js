// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import bindValueToOnChange from '../../../utils/TestHelper/bindValueToOnChange';
import EditLine from '../EditLine';

test('Render an EditLine', () => {
    const {asFragment} = render(<EditLine id="1" onChange={jest.fn()} onRemove={jest.fn()} value="Test" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Call onChange callback if input changes', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    render(bindValueToOnChange(
        <EditLine id={3} onChange={changeSpy} onRemove={jest.fn()} value="old" />,
        {valueArgIndex: 1}
    ));

    const textbox = screen.getByRole('textbox');
    await user.clear(textbox);
    await user.type(textbox, 'new');

    expect(changeSpy).toHaveBeenLastCalledWith(3, 'new');
});

test('Call onRemove callback if line is removed', async() => {
    const user = userEvent.setup();
    const removeSpy = jest.fn();
    render(<EditLine id={3} onChange={jest.fn()} onRemove={removeSpy} value="old" />);

    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));

    expect(removeSpy).toBeCalledWith(3);
});
