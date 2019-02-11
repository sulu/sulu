// @flow
import React from 'react';
import log from 'loglevel';
import moment from 'moment-timezone';
import DateTimeBlockPreviewTransformer from '../../blockPreviewTransformers/DateTimeBlockPreviewTransformer';

beforeEach(() => {
    moment.tz.setDefault('Europe/Vienna');
});

const dateTimeBlockPreviewTransformer = new DateTimeBlockPreviewTransformer();

jest.mock('loglevel', () => ({
    error: jest.fn(),
}));

test('Test undefined', () => {
    expect(dateTimeBlockPreviewTransformer.transform(undefined)).toBe(null);
});

test('Test invalid format', () => {
    expect(dateTimeBlockPreviewTransformer.transform('xxx')).toBe(null);
    expect(log.error).toBeCalledWith('Invalid date given: "xxx". Format needs to be "YYYY-MM-DD"');
});

test('Test valid example', () => {
    expect(dateTimeBlockPreviewTransformer.transform('2018-03-10T14:09:04+01:00')).toEqual(<p>03/10/2018</p>);
});
