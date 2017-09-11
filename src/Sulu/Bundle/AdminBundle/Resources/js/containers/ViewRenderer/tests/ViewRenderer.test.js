/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from 'enzyme';
import ViewRenderer from '../ViewRenderer';
import viewStore from '../stores/ViewStore';

jest.mock('../stores/ViewStore', () => ({
    get: jest.fn(),
}));

test('Render view returned from ViewRegistry', () => {
    viewStore.get.mockReturnValue(() => (<h1>Test</h1>));
    const view = render(<ViewRenderer name="test" />);
    expect(view).toMatchSnapshot();
    expect(viewStore.get).toBeCalledWith('test');
});

test('Render view returned from ViewRegistry with passed router', () => {
    const router = {
        attributes: {
            value: 'Test attribute',
        },
    };

    viewStore.get.mockReturnValue((props) => (<h1>{props.router.attributes.value}</h1>));
    const view = render(<ViewRenderer name="test" router={router} />);
    expect(view).toMatchSnapshot();
    expect(viewStore.get).toBeCalledWith('test');
});

test('Render view should throw if view does not exist', () => {
    viewStore.get.mockReturnValue(undefined);
    expect(() => render(<ViewRenderer name="not_existing" />)).toThrow(/not_existing/);
});
