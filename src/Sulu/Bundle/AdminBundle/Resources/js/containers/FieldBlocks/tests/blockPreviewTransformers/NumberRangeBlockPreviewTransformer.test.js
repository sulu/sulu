// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import NumberRangeBlockPreviewTransformer from '../../blockPreviewTransformers/NumberRangeBlockPreviewTransformer';

function renderPreview(value: mixed) {
    return render(<div>{new NumberRangeBlockPreviewTransformer().transform(value)}</div>);
}

test('Render both bounds of a range', () => {
    renderPreview({from: -20, to: 60});

    expect(screen.getByText('-20 – 60')).toBeInTheDocument();
});

test('Render a range with one bound only', () => {
    renderPreview({from: 5, to: null});

    expect(screen.getByText('5 –')).toBeInTheDocument();
});

test('Return null without any bound', () => {
    expect(new NumberRangeBlockPreviewTransformer().transform({from: null, to: undefined})).toBeNull();
});

test('Return null for a value that is no range', () => {
    const transformer = new NumberRangeBlockPreviewTransformer();

    expect(transformer.transform(null)).toBeNull();
    expect(transformer.transform(42)).toBeNull();
});
