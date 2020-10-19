// @flow
import React from 'react';
import log from 'loglevel';
import ColorFieldTransformer from '../../fieldTransformers/ColorFieldTransformer';
import colorFieldTransformerStyles from './colorFieldTransformer.scss';

const colorTransformer = new ColorFieldTransformer();

jest.mock('loglevel', () => ({
    error: jest.fn(),
}));

test('Test invalid color null', () => {
    const value = null;
    expect(colorTransformer.transform(value)).toBe(value);
});

test('Test invalid color (no hashtag)', () => {
    const value = 'FFF';
    expect(colorTransformer.transform(value)).toBe(null);
    expect(log.error).toBeCalledWith(`Transformer parameter "${value}" needs to be of type hexadecimal color.`);
});

test('Test invalid color (length 2)', () => {
    const value = '#FF';
    expect(colorTransformer.transform(value)).toBe(null);
    expect(log.error).toBeCalledWith(`Transformer parameter "${value}" needs to be of type hexadecimal color.`);
});

test('Test valid color (lowercase)', () => {
    const value = '#ffffff';
    const style = {};
    style.backgroundColor = value;
    expect(colorTransformer.transform(value)).toEqual(
        <div className={colorFieldTransformerStyles.colorBox} style={style}></div>
    );
});

test('Test valid color (uppercase)', () => {
    const value = '#FFFFFF';
    const style = {};
    style.backgroundColor = value;
    expect(colorTransformer.transform(value)).toEqual(
        <div className={colorFieldTransformerStyles.colorBox} style={style}></div>
    );
});

test('Test valid color (3 length)', () => {
    const value = '#FFF';
    const style = {};
    style.backgroundColor = value;
    expect(colorTransformer.transform(value)).toEqual(
        <div className={colorFieldTransformerStyles.colorBox} style={style}></div>
    );
});
