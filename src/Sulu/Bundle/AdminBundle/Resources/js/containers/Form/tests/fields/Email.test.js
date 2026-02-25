// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Email from '../../fields/Email';

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

test('Pass error correctly to component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <Email
            {...createProps()}
            error={error}
        />
    );
    const input = screen.getByRole('textbox');

    expect(input).toHaveAttribute('type', 'email');
    expect(input.parentElement).toHaveClass('error');
});

test('Pass props correctly to component', () => {
    render(
        <Email
            {...createProps()}
            disabled={true}
        />
    );
    const input = screen.getByRole('textbox');

    expect(input).toHaveAttribute('type', 'email');
    expect(input).toBeDisabled();
    expect(input.parentElement).not.toHaveClass('error');
});
