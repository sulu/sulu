/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import ReactTestRenderer from 'react-test-renderer';
import Application from '../Application';

jest.mock('../../ViewRenderer', () => function Test() {
    return (<h1>Test</h1>);
});

test('Application should not fail if current route does not exist', () => {
    const router = jest.fn();
    const view = ReactTestRenderer.create(<Application router={router} />);

    expect(view).toMatchSnapshot();
});

test('Application should render based on current route', () => {
    const router = {
        currentRoute: {
            view: 'test',
        },
    };

    const view = ReactTestRenderer.create(<Application router={router} />);

    expect(view).toMatchSnapshot();
});
