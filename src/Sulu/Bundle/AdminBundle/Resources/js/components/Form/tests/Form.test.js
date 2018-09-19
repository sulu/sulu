// @flow
import React from 'react';
import {render} from 'enzyme';
import Form from '../Form';

test('Render a form', () => {
    expect(render(<Form>Test</Form>)).toMatchSnapshot();
});
