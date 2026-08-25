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

test('Test zero', () => {
    expect(stringFieldTransformer.transform(0)).toEqual(<span className="textBox" title={0}>{0}</span>);
});

test('Test null', () => {
    expect(stringFieldTransformer.transform(null)).toBe(null);
});

test('Test empty string', () => {
    expect(stringFieldTransformer.transform('')).toBe(null);
});
