// @flow
import React from 'react';
import userEvent from '@testing-library/user-event';
import {render, screen} from '@testing-library/react';
import Input from '../../ruleTypes/Input';

class InputHarness extends React.Component<{|name: string, onChange: Function, value: string|}, {|value: string|}> {
    state = {
        value: this.props.value,
    };

    handleChange = (newValue) => {
        const {name, onChange} = this.props;
        this.setState({value: newValue[name]});
        onChange(newValue);
    };

    render() {
        const {name} = this.props;
        const {value} = this.state;

        return <Input onChange={this.handleChange} options={{name}} value={{[name]: value}} />;
    }
}

test.each([
    ['test1', 'value1'],
    ['test2', 'value2'],
])('Pass the change callback for "%s" with a value of "%s"', async(name, value) => {
    const changeSpy = jest.fn();
    const user = userEvent.setup();

    render(<InputHarness name={name} onChange={changeSpy} value="" />);
    await user.type(screen.getByRole('textbox'), value);

    expect(changeSpy).toHaveBeenLastCalledWith({[name]: value});
});

test.each([
    ['test1', 'value1'],
    ['test2', 'value2'],
])('Pass value for "%s" with a value of "%s" correctly to Input', (name, value) => {
    render(<Input onChange={jest.fn()} options={{name}} value={{[name]: value}} />);
    expect(screen.getByRole('textbox')).toHaveValue(value);
});
