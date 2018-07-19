// @flow
import React from 'react';
import {render} from 'enzyme';
import Item from '../Item';

test('Should render item as not selected by default', () => {
    expect(render(<Item id={1}>Test</Item>)).toMatchSnapshot();
});

test('Should render item as selected', () => {
    expect(render(<Item id={1} selected={true}>Test</Item>)).toMatchSnapshot();
});

test('Should render item as disabled', () => {
    expect(render(<Item id={1} disabled={true}>Test</Item>)).toMatchSnapshot();
});

test('Should render item with indicators', () => {
    const indicators = [
        <span key={1}>ghost</span>,
        <span key={2}>shadow</span>,
    ];

    expect(render(<Item id={2} indicators={indicators}>Test with indicators</Item>)).toMatchSnapshot();
});
