// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Email from '../../fields/Email';

test('Pass error correctly to component', () => {
    const formInspector = ({locale: undefined}: any);
    const error = {keyword: 'minLength', parameters: {}};

    render(<Email {...fieldTypeDefaultProps} error={error} formInspector={formInspector} />);

    expect(screen.getByRole('textbox').parentElement).toHaveClass('error');
});

test('Pass props correctly to component', () => {
    const formInspector = ({locale: undefined}: any);

    render(
        <Email
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(screen.getByRole('textbox').parentElement).not.toHaveClass('error');
    expect(screen.getByRole('textbox')).toBeDisabled();
});
