// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Phone from '../../fields/Phone';

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

test('Pass error correctly to component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <Phone
            {...createProps()}
            error={error}
        />
    );
    const input = screen.getByRole('textbox');

    expect(input).toHaveAttribute('type', 'tel');
    expect(input.parentElement).toHaveClass('error');
});

test('Pass props correctly to component', () => {
    render(
        <Phone
            {...createProps()}
            disabled={true}
        />
    );
    const input = screen.getByRole('textbox');

    expect(input).toHaveAttribute('type', 'tel');
    expect(input).toBeDisabled();
    expect(input.parentElement).not.toHaveClass('error');
});
