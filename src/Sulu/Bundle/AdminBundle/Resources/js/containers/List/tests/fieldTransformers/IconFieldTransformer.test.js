// @flow
import React from 'react';
import log from 'loglevel';
import classNames from 'classnames';
import Icon from '../../../../components/Icon';
import IconFieldTransformer from '../../fieldTransformers/IconFieldTransformer';
import iconFieldTransformerStyles from '../../fieldTransformers/iconFieldTransformer.scss';

const iconFieldTransformer = new IconFieldTransformer();

jest.mock('loglevel', () => ({
    error: jest.fn(),
    warn: jest.fn(),
}));

test('Test value undefined', () => {
    expect(iconFieldTransformer.transform(undefined, {})).toBe(undefined);
});

test('Test value null', () => {
    expect(iconFieldTransformer.transform(null, {})).toBe(null);
});

test('Test parameters/mapping undefined', () => {
    expect(iconFieldTransformer.transform('failed', {})).toBe('failed');
});

test('Test parameters/mapping wrong type', () => {
    expect(iconFieldTransformer.transform('failed', {mapping: 'foo'})).toBe(null);
    expect(log.error).toBeCalledWith('Transformer parameter "mapping" needs to be of type collection.');
});

test('Test parameters/mapping empty', () => {
    expect(iconFieldTransformer.transform('failed', {mapping: {}})).toBe('failed');
});

test('Test icon wrong type', () => {
    expect(iconFieldTransformer.transform('failed', {mapping: {failed: 1}})).toBe(null);
    expect(log.error).toBeCalledWith(
        'Transformer parameter "mapping/failed" needs to be either of type string or collection.'
    );
});

test('Test parameters/default wrong type', () => {
    expect(iconFieldTransformer.transform('default_failed', {
        mapping: {failed: 'su-fail'},
        default: 1}
    )).toBe('default_failed');
    expect(log.warn).toBeCalledWith(
        'Transformer parameter "default" needs to be of type string or collection, number given.'
    );
});

test('Test icon is object without icon', () => {
    expect(iconFieldTransformer.transform('failed', {mapping: {failed: {}}})).toBe(null);
    expect(log.error).toBeCalledWith('Transformer parameter "mapping/failed/icon" needs to be of type string.');
});

test('Test icon is object with icon having wrong type', () => {
    expect(iconFieldTransformer.transform('failed', {mapping: {failed: {icon: 1}}})).toBe(null);
    expect(log.error).toBeCalledWith('Transformer parameter "mapping/failed/icon" needs to be of type string.');
});

test('Test icon is object with color having wrong type', () => {
    expect(iconFieldTransformer.transform('failed', {mapping: {failed: {icon: 'su-ban', color: ['bar']}}})).toBe(null);
    expect(log.error).toBeCalledWith('Transformer parameter "mapping/failed/color" needs to be of type string.');
});

test('Test icon not configured', () => {
    expect(iconFieldTransformer.transform('succeeded', {mapping: {failed: 'su-ban'}})).toBe('succeeded');
    expect(log.warn).toBeCalledWith(
        'There was no icon specified in the "mapping" transformer parameter for the value "succeeded".'
    );
});

test('Test icon string', () => {
    expect(iconFieldTransformer.transform('failed', {mapping: {failed: 'su-ban'}})).toEqual(
        <Icon
            className={classNames(iconFieldTransformerStyles.listIcon, iconFieldTransformerStyles.default)}
            name="su-ban"
        />
    );
});

test('Test icon object', () => {
    expect(iconFieldTransformer.transform('failed', {mapping: {failed: {icon: 'su-ban'}}})).toEqual(
        <Icon
            className={classNames(iconFieldTransformerStyles.listIcon, iconFieldTransformerStyles.default)}
            name="su-ban"
            style={{}}
        />
    );
});

test('Test parameters/default string', () => {
    expect(iconFieldTransformer.transform('default_failed', {
        default: 'su-default-ban',
        mapping: {failed: 'su-ban'},
    })).toEqual(
        <Icon
            className={classNames(iconFieldTransformerStyles.listIcon, iconFieldTransformerStyles.default)}
            name="su-default-ban"
        />
    );
});

test('Test parameters/default object', () => {
    expect(iconFieldTransformer.transform('default_failed', {
        default: {
            icon: 'su-default-ban',
            color: 'red',
        },
        mapping: {
            failed: {
                icon: 'su-ban',
            },
        },
    })).toEqual(
        <Icon
            className={classNames(iconFieldTransformerStyles.listIcon, iconFieldTransformerStyles.default)}
            name="su-default-ban"
            style={{color: 'red'}}
        />
    );
});

test('Test icon object with color', () => {
    expect(iconFieldTransformer.transform('failed', {mapping: {failed: {icon: 'su-ban', color: 'red'}}})).toEqual(
        <Icon
            className={classNames(iconFieldTransformerStyles.listIcon, iconFieldTransformerStyles.default)}
            name="su-ban"
            style={{color: 'red'}}
        />
    );
});

test('Test dark skin', () => {
    expect(iconFieldTransformer.transform('failed', {skin: 'dark', mapping: {failed: 'su-ban'}})).toEqual(
        <Icon
            className={classNames(iconFieldTransformerStyles.listIcon, iconFieldTransformerStyles.dark)}
            name="su-ban"
        />
    );
});

test('Test invalid skin type', () => {
    iconFieldTransformer.transform('failed', {skin: 123, mapping: {failed: 'su-ban'}});

    expect(log.error).toBeCalledWith('Transformer parameter "skin" needs to be of type string, number given.');
});
