/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import ReactTestRenderer from 'react-test-renderer';
import ViewRenderer from '../ViewRenderer';
import viewStore from '../stores/ViewStore';

jest.mock('../stores/ViewStore', () => ({
    get: jest.fn(),
}));

test('Render view returned from ViewRegistry', () => {
    viewStore.get.mockReturnValue(() => (<h1>Test</h1>));
    const view = ReactTestRenderer.create(<ViewRenderer name="test" />);
    expect(view).toMatchSnapshot();
    expect(viewStore.get).toBeCalledWith('test');
});

test('Render view returned from ViewRegistry with passed props', () => {
    viewStore.get.mockReturnValue((props) => (<h1>{props.value}</h1>));
    const view = ReactTestRenderer.create(<ViewRenderer name="test" parameters={{value: 'Test from props'}} />);
    expect(view).toMatchSnapshot();
    expect(viewStore.get).toBeCalledWith('test');
});

test('Render view should throw if view does not exist', () => {
    viewStore.get.mockReturnValue(undefined);
    expect(() => ReactTestRenderer.create(<ViewRenderer name="not_existing" />)).toThrow(/not_existing/);
});
