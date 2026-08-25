// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import MultiSelect from '../../MultiSelect';

const Option = MultiSelect.Option;
const Divider = MultiSelect.Divider;

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

function renderMultiSelect(props: any = {}) {
    return render(
        <MultiSelect
            allSelectedText="All selected"
            noneSelectedText="None selected"
            onChange={jest.fn()}
            {...props}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </MultiSelect>
    );
}

function getDisplayButton() {
    const displayButton = document.querySelector('button.displayValue');

    if (!displayButton) {
        throw new Error('Expected display value button');
    }

    return displayButton;
}

test('The component should render a generic select', () => {
    renderMultiSelect();

    expect(getDisplayButton()).toHaveTextContent('None selected');
});

test('The component should pass the disabled value to the select component', () => {
    renderMultiSelect({disabled: true});

    expect(getDisplayButton()).toBeDisabled();
});

test('The component should pass the correct display value if nothing is selected', () => {
    renderMultiSelect();

    expect(getDisplayButton()).toHaveTextContent('None selected');
});

test('The component should pass the correct display value if everything is selected', () => {
    renderMultiSelect({values: ['option-1', 'option-2', 'option-3']});

    expect(getDisplayButton()).toHaveTextContent('All selected');
});

test('The component should pass the correct display value if some options are selected', () => {
    renderMultiSelect({values: ['option-1', 'option-2']});

    expect(getDisplayButton()).toHaveTextContent('Option 1, Option 2');
});

test('The component should select the correct option', async() => {
    const user = userEvent.setup();
    renderMultiSelect({values: ['option-1', 'option-2']});

    await user.click(getDisplayButton());

    const selectedButtons = screen.getAllByRole('button', {name: /Option \d/})
        .filter((button) => button.classList.contains('selected'));
    expect(selectedButtons).toHaveLength(2);
    expect(screen.getByRole('button', {name: /Option 1$/})).toHaveClass('selected');
    expect(screen.getByRole('button', {name: /Option 2$/})).toHaveClass('selected');
    expect(screen.getByRole('button', {name: /Option 3$/})).not.toHaveClass('selected');
});

test('The component should trigger the change callback on select with an added value', async() => {
    const user = userEvent.setup();
    const onChangeSpy = jest.fn();

    renderMultiSelect({
        onChange: onChangeSpy,
        values: ['option-1', 'option-2'],
    });

    await user.click(getDisplayButton());
    await user.click(screen.getByRole('button', {name: /Option 3$/}));

    expect(onChangeSpy).toHaveBeenCalledWith(['option-1', 'option-2', 'option-3']);
});

test('The component should trigger the change callback on select with a removed value', async() => {
    const user = userEvent.setup();
    const onChangeSpy = jest.fn();

    renderMultiSelect({
        onChange: onChangeSpy,
        values: ['option-1', 'option-2'],
    });

    await user.click(getDisplayButton());
    await user.click(screen.getByRole('button', {name: /Option 2$/}));

    expect(onChangeSpy).toHaveBeenCalledWith(['option-1']);
});

test('The component should trigger the close callback when the MultiSelect is closed', async() => {
    const user = userEvent.setup();
    const closeSpy = jest.fn();

    renderMultiSelect({
        onClose: closeSpy,
    });

    expect(closeSpy).not.toHaveBeenCalled();
    await user.click(getDisplayButton());
    await user.click(screen.getByTestId('backdrop'));
    expect(closeSpy).toHaveBeenCalled();
});
