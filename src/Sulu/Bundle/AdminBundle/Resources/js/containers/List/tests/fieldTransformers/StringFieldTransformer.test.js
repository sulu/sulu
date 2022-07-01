// @flow
import React from 'react';
import StringFieldTransformer from '../../fieldTransformers/StringFieldTransformer';

const stringFieldTransformer = new StringFieldTransformer();

jest.mock('loglevel', () => ({
    error: jest.fn(),
}));

test('Test undefined', () => {
    expect(stringFieldTransformer.transform(undefined)).toBe(null);
});

test('Test string', () => {
    expect(stringFieldTransformer.transform('Test1')).toEqual(<span className="textBox" title="Test1">Test1</span>);
});

test('Test number', () => {
    expect(stringFieldTransformer.transform(5)).toEqual(<span className="textBox" title={5}>{5}</span>);
});
