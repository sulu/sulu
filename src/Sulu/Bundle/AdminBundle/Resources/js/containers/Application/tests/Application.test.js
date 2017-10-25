/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from 'enzyme';
import Application from '../Application';

jest.mock('../../ViewRenderer', () => function Test(props) {
    return (
        <div>
            <h1>Test</h1>
            <h2>{props.router.route.view}</h2>
        </div>
    );
});

test('Application should not fail if current route does not exist', () => {
    const router = jest.fn();
    const view = render(<Application router={router} />);

    expect(view).toMatchSnapshot();
});

test('Application should render based on current route', () => {
    const router = {
        route: {
            name: 'test',
            view: 'test',
        },
    };

    const view = render(<Application router={router} />);

    expect(view).toMatchSnapshot();
});
