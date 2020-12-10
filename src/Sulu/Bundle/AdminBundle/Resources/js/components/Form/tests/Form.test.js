// @flow
import React from 'react';
import {render} from 'enzyme';
import Form from '../Form';

test('Render a form', () => {
    expect(render(
        <Form skin="dark">
            <Form.Field label="Test1">Test 1</Form.Field>
            <Form.Field label="Test1">Test 2</Form.Field>
        </Form>
    )).toMatchSnapshot();
});
