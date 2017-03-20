/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import renderer from 'react-test-renderer';
import Login from '../Login';

test('Render login', () => {
    const login = renderer.create(<Login />);
    expect(login).toMatchSnapshot();
});
