//@flow
import React from 'react';
import {render, mount} from 'enzyme';
import Application from '../Application';
import Router from '../../../services/Router';

jest.mock('../../../services/Router', () => function() {});

jest.mock('../../ViewRenderer', () => function Test(props) {
    return (
        <div>
            <h1>Test</h1>
            <h2>{props.router.route.view}</h2>
        </div>
    );
});

test('Application should not fail if current route does not exist', () => {
    const router = new Router({});
    const view = render(<Application router={router} />);

    expect(view).toMatchSnapshot();
});

test('Application should render based on current route', () => {
    const router = new Router({});
    router.route = {
        name: 'test',
        view: 'test',
        attributeDefaults: {},
        rerenderAttributes: [],
        path: '/webspaces',
        children: [],
        options: {},
        parent: null,
    };

    const view = render(<Application router={router} />);

    expect(view).toMatchSnapshot();
});

test('Application should render opened navigation', () => {
    const router = new Router({});
    router.route = {
        name: 'test',
        view: 'test',
        attributeDefaults: {},
        rerenderAttributes: [],
        path: '/webspaces',
        children: [],
        options: {},
        parent: null,
    };

    const view = mount(<Application router={router} />);
    view.find('Button[icon="su-bars"]').simulate('click');

    expect(view).toMatchSnapshot();
});
