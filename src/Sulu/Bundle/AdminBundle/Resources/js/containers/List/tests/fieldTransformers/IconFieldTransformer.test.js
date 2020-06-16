// @flow
import React from 'react';
import log from 'loglevel';
import IconFieldTransformer from '../../fieldTransformers/IconFieldTransformer';
import iconFieldTransformerStyles from '../../fieldTransformers/iconFieldTransformer.scss';
import Icon from '../../../../components/Icon';

const iconFieldTransformer = new IconFieldTransformer();

jest.mock('loglevel', () => ({
    error: jest.fn(),
}));

test('Test value undefined', () => {
    expect(iconFieldTransformer.transform(undefined, {})).toBe(undefined);
});

test('Test value null', () => {
    expect(iconFieldTransformer.transform(null, {})).toBe(null);
});

test('Test parameters undefined', () => {
    expect(iconFieldTransformer.transform('failed', undefined)).toBe('failed');
});

test('Test parameters/icons undefined', () => {
    expect(iconFieldTransformer.transform('failed', {})).toBe('failed');
});

test('Test parameters/icons wrong type', () => {
    expect(iconFieldTransformer.transform('failed', {icons: 'foo'})).toBe(null);
    expect(log.error).toBeCalledWith('Parameter "icons" needs to be of type collection.');
});

test('Test parameters/icons empty', () => {
    expect(iconFieldTransformer.transform('failed', {icons: {}})).toBe('failed');
});

test('Test icon wrong type', () => {
    expect(iconFieldTransformer.transform('failed', {icons: {failed: 1}})).toBe(null);
    expect(log.error).toBeCalledWith('Parameter "icons/failed" needs to be either of type string or collection.');
});

test('Test icon is object without icon', () => {
    expect(iconFieldTransformer.transform('failed', {icons: {failed: {}}})).toBe(null);
    expect(log.error).toBeCalledWith('Parameter "icons/failed/icon" needs to be set.');
});

test('Test icon is object with icon having wrong type', () => {
    expect(iconFieldTransformer.transform('failed', {icons: {failed: {icon: 1}}})).toBe(null);
    expect(log.error).toBeCalledWith('Parameter "icons/failed/icon" needs to be of type string.');
});

test('Test icon is object with color having wrong type', () => {
    expect(iconFieldTransformer.transform('failed', {icons: {failed: {icon: 'su-ban', color: ['bar']}}})).toBe(null);
    expect(log.error).toBeCalledWith('Parameter "icons/failed/color" needs to be of type string.');
});

test('Test icon not configured', () => {
    expect(iconFieldTransformer.transform('succeeded', {icons: {failed: 'su-ban'}})).toBe('succeeded');
});

test('Test icon string', () => {
    expect(iconFieldTransformer.transform('failed', {icons: {failed: 'su-ban'}})).toEqual(
        <Icon className={iconFieldTransformerStyles.listIcon} name="su-ban" style={{color: null}} />
    );
});

test('Test icon object', () => {
    expect(iconFieldTransformer.transform('failed', {icons: {failed: {icon: 'su-ban'}}})).toEqual(
        <Icon className={iconFieldTransformerStyles.listIcon} name="su-ban" style={{color: null}} />
    );
});

test('Test icon object with color', () => {
    expect(iconFieldTransformer.transform('failed', {icons: {failed: {icon: 'su-ban', color: 'red'}}})).toEqual(
        <Icon className={iconFieldTransformerStyles.listIcon} name="su-ban" style={{color: 'red'}} />
    );
});
