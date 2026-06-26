// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import EditLine from '../EditLine';

type ControlledEditLineProps = {|
    changeSpy: (id: number, value: ?string) => void,
|};

type ControlledEditLineState = {|
    value: string,
|};

class ControlledEditLine extends React.Component<ControlledEditLineProps, ControlledEditLineState> {
    state = {
        value: 'old',
    };

    handleChange = (id: number, value: ?string) => {
        this.setState({value: value || ''});
        this.props.changeSpy(id, value);
    };

    handleRemove = () => {};

    render() {
        return (
            <EditLine
                id={3}
                onChange={this.handleChange}
                onRemove={this.handleRemove}
                value={this.state.value}
            />
        );
    }
}

test('Render an EditLine', () => {
    const {container} = render(<EditLine id="1" onChange={jest.fn()} onRemove={jest.fn()} value="Test" />);

    expect(container).toMatchSnapshot();
});

test('Call onChange callback if input changes', async() => {
    const changeSpy = jest.fn();

    render(<ControlledEditLine changeSpy={changeSpy} />);
    await userEvent.clear(screen.getByDisplayValue('old'));
    await userEvent.type(screen.getByRole('textbox'), 'new');

    expect(changeSpy).toHaveBeenCalledWith(3, 'new');
});

test('Call onRemove callback if line is removed', async() => {
    const removeSpy = jest.fn();

    render(<EditLine id={3} onChange={jest.fn()} onRemove={removeSpy} value="old" />);
    await userEvent.click(screen.getByLabelText('su-trash-alt'));

    expect(removeSpy).toHaveBeenCalledWith(3);
});
