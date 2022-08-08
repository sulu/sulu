// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Form from '../Form';

test('Render a form', () => {
    const {container} = render(
        <Form skin="dark">
            <Form.Field label="Test1">Test 1</Form.Field>
            <Form.Field label="Test1">Test 2</Form.Field>
        </Form>
    );
    expect(container).toMatchSnapshot();
});
