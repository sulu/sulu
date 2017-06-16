/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import ReactTestRenderer from 'react-test-renderer';
import ViewRenderer from '../ViewRenderer';
import ViewRegistry from '../../../services/ViewRegistry';

jest.mock('../../../services/ViewRegistry', () => {
    return {
        getView: jest.fn(),
    };
});

test('Render view returned from ViewRegistry', () => {
    ViewRegistry.getView.mockReturnValue(() => (<h1>Test</h1>));
    const view = ReactTestRenderer.create(<ViewRenderer name="test" />);
    expect(view).toMatchSnapshot();
    expect(ViewRegistry.getView).toBeCalledWith('test');
});

test('Render view returned from ViewRegistry with passed props', () => {
    ViewRegistry.getView.mockReturnValue((props) => (<h1>{props.value}</h1>));
    const view = ReactTestRenderer.create(<ViewRenderer name="test" parameters={{value: 'Test from props'}} />);
    expect(view).toMatchSnapshot();
    expect(ViewRegistry.getView).toBeCalledWith('test');
});
