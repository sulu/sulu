/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import renderer from 'react-test-renderer';
import {addView, ViewRenderer} from '../ViewRegistry';

test('Add view to registry and render it', () => {
    addView('test', (props) => <h1>{props.title}</h1>);

    const view = renderer.create(<ViewRenderer name="test" parameters={{title: 'Test'}} />);
    expect(view).toMatchSnapshot();
});
