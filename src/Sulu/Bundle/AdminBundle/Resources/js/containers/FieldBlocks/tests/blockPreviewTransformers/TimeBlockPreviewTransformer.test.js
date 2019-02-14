// @flow
import React from 'react';
import log from 'loglevel';
import moment from 'moment-timezone';
import TimeBlockPreviewTransformer from '../../blockPreviewTransformers/TimeBlockPreviewTransformer';

beforeEach(() => {
    moment.tz.setDefault('Europe/Vienna');
});

const timeBlockPreviewTransformer = new TimeBlockPreviewTransformer();

jest.mock('loglevel', () => ({
    error: jest.fn(),
}));

test('Test undefined', () => {
    expect(timeBlockPreviewTransformer.transform(undefined)).toBe(null);
});

test('Test invalid format', () => {
    expect(timeBlockPreviewTransformer.transform('xxx')).toBe(null);
    expect(log.error).toBeCalledWith('Invalid time given: "xxx". Format needs to be "HH:mm:ss"');
});

test('Test valid example', () => {
    expect(timeBlockPreviewTransformer.transform('14:09:04')).toEqual(<p>2:09 PM</p>);
});
