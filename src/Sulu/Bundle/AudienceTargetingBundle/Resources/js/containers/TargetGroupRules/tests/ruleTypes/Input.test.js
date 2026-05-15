// @flow
import React from 'react';
import userEvent from '@testing-library/user-event';
import {render, screen} from '@testing-library/react';
import Input from '../../ruleTypes/Input';

function InputHarness(props: {|name: string, onChange: Function, value: string|}) {
    const [inputValue, setInputValue] = React.useState(props.value);

    function handleChange(newValue) {
        const {name, onChange} = props;
        setInputValue(newValue[name]);
        onChange(newValue);
    }

    /* eslint-disable-next-line react/jsx-no-bind */
    return <Input onChange={handleChange} options={{name: props.name}} value={{[props.name]: inputValue}} />;
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
