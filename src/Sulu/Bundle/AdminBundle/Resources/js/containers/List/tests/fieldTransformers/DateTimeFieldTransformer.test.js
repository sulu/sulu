// @flow
import React from 'react';
import log from 'loglevel';
import moment from 'moment-timezone';
import DateTimeFieldTransformer from '../../fieldTransformers/DateTimeFieldTransformer';
import {translate} from '../../../../utils';

beforeEach(() => {
    moment.tz.setDefault('Europe/Vienna');
});

const dateTimeFieldTransformer = new DateTimeFieldTransformer();

jest.mock('loglevel', () => ({
    error: jest.fn(),
}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Test undefined', () => {
    expect(dateTimeFieldTransformer.transform(undefined, {})).toBe(null);
});

test('Test invalid format', () => {
    expect(dateTimeFieldTransformer.transform('xxx', {})).toBe(null);
    expect(log.error).toBeCalledWith(
        'Invalid date given: "xxx". Format needs to be in "ISO 8601" or a valid timestamp.'
    );
});

test('Test valid example', () => {
    expect(
        dateTimeFieldTransformer.transform('2018-03-10T14:09:04+01:00', {})
    ).toEqual(<span className="default">March 10, 2018 2:09 PM</span>);
});

test('Test light skin example', () => {
    expect(
        dateTimeFieldTransformer.transform('2018-03-10T14:09:04+01:00', {'skin': 'light'})
    ).toEqual(<span className="light">March 10, 2018 2:09 PM</span>);
});

test('Test invalid skin type', () => {
    dateTimeFieldTransformer.transform('2018-03-10T14:09:04+01:00', {'skin': 123});

    expect(log.error).toBeCalledWith('Transformer parameter "skin" needs to be of type string, number given.');
});

test('Test relative format sameDay example', () => {
    const dateTime = dateTimeFieldTransformer.transform(moment(), {format: 'relative'});

    // $FlowFixMe
    expect(dateTime.props.children).toContain('sulu_admin.sameDay');
    expect(translate).toHaveBeenCalledWith('sulu_admin.sameDay');
});

test('Test relative format nextDay example', () => {
    const dateTime = dateTimeFieldTransformer.transform(moment().add(1, 'day'), {format: 'relative'});

    // $FlowFixMe
    expect(dateTime.props.children).toContain('sulu_admin.nextDay');
    expect(translate).toHaveBeenCalledWith('sulu_admin.nextDay');
});

test('Test relative format lastDay example', () => {
    const dateTime = dateTimeFieldTransformer.transform(moment().subtract(1, 'day'), {format: 'relative'});

    // $FlowFixMe
    expect(dateTime.props.children).toContain('sulu_admin.lastDay');
    expect(translate).toHaveBeenCalledWith('sulu_admin.lastDay');
});

test('Test relative format lastWeek example', () => {
    const momentObject = moment().subtract(7, 'day');
    const dateTime = dateTimeFieldTransformer.transform(momentObject, {format: 'relative'});

    // $FlowFixMe
    expect(dateTime.props.children).toContain(momentObject.format('LLL'));
    expect(translate).toHaveBeenCalledWith('sulu_admin.lastDay');
});

test('Test timestamp in seconds', () => {
    // March 10, 2018 1:09:04 PM UTC (timestamp: 1520690944)
    const timestamp = 1520690944;
    const result = dateTimeFieldTransformer.transform(timestamp, {});

    // Check that it's a valid React element
    expect(React.isValidElement(result)).toBe(true);
    // $FlowFixMe
    expect(result.type).toBe('span');
    // $FlowFixMe
    expect(result.props.className).toBe('default');
    // $FlowFixMe
    expect(result.props.children).toContain('March 10, 2018');
});

test('Test timestamp in milliseconds', () => {
    // March 10, 2018 1:09:04 PM UTC (timestamp: 1520690944000)
    const timestamp = 1520690944000;
    const result = dateTimeFieldTransformer.transform(timestamp, {});

    expect(React.isValidElement(result)).toBe(true);
    // $FlowFixMe
    expect(result.type).toBe('span');
    // $FlowFixMe
    expect(result.props.className).toBe('default');
    // $FlowFixMe
    expect(result.props.children).toContain('March 10, 2018');
});

test('Test timestamp as string (seconds)', () => {
    // March 10, 2018 1:09:04 PM UTC (timestamp: "1520690944")
    const timestamp = '1520690944';
    const result = dateTimeFieldTransformer.transform(timestamp, {});

    expect(React.isValidElement(result)).toBe(true);
    // $FlowFixMe
    expect(result.type).toBe('span');
    // $FlowFixMe
    expect(result.props.className).toBe('default');
    // $FlowFixMe
    expect(result.props.children).toContain('March 10, 2018');
});

test('Test timestamp as string (milliseconds)', () => {
    // March 10, 2018 1:09:04 PM UTC (timestamp: "1520690944000")
    const timestamp = '1520690944000';
    const result = dateTimeFieldTransformer.transform(timestamp, {});

    expect(React.isValidElement(result)).toBe(true);
    // $FlowFixMe
    expect(result.type).toBe('span');
    // $FlowFixMe
    expect(result.props.className).toBe('default');
    // $FlowFixMe
    expect(result.props.children).toContain('March 10, 2018');
});

test('Test timestamp with light skin', () => {
    const timestamp = 1520690944;
    const result = dateTimeFieldTransformer.transform(timestamp, {'skin': 'light'});

    expect(React.isValidElement(result)).toBe(true);
    // $FlowFixMe
    expect(result.type).toBe('span');
    // $FlowFixMe
    expect(result.props.className).toBe('light');
    // $FlowFixMe
    expect(result.props.children).toContain('March 10, 2018');
});

test('Test timestamp with relative format - current timestamp', () => {
    const currentTimestamp = Math.floor(Date.now() / 1000);
    const dateTime = dateTimeFieldTransformer.transform(currentTimestamp, {format: 'relative'});

    // $FlowFixMe
    expect(dateTime.props.children).toContain('sulu_admin.sameDay');
    expect(translate).toHaveBeenCalledWith('sulu_admin.sameDay');
});

test('Test invalid timestamp (non-numeric string)', () => {
    expect(dateTimeFieldTransformer.transform('not-a-timestamp', {})).toBe(null);
    expect(log.error).toBeCalledWith(
        'Invalid date given: "not-a-timestamp". Format needs to be in "ISO 8601" or a valid timestamp.'
    );
});

test('Test negative timestamp', () => {
    // December 31, 1969 23:59:59 UTC (timestamp: -1)
    const timestamp = -1;
    const result = dateTimeFieldTransformer.transform(timestamp, {});

    expect(React.isValidElement(result)).toBe(true);
    // $FlowFixMe
    expect(result.type).toBe('span');
    // $FlowFixMe
    expect(result.props.className).toBe('default');
    // Check for December 31, 1969 or January 1, 1970 depending on timezone
    // $FlowFixMe
    const dateString = result.props.children;
    expect(dateString).toMatch(/December 31, 1969|January 1, 1970/);
});
