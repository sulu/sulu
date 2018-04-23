// @flow
import log from 'loglevel';
import moment from 'moment-timezone';
import DateFieldTransformer from '../../fieldTransformers/DateFieldTransformer';

const dateFieldTransformer = new DateFieldTransformer();

beforeEach(() => {
    moment.tz.setDefault('Europe/Vienna');
});

jest.mock('loglevel', () => ({
    error: jest.fn(),
}));

test('Test undefined', () => {
    expect(dateFieldTransformer.transform(undefined)).toBe(undefined);
});

test('Test invalid format', () => {
    expect(dateFieldTransformer.transform('xxx')).toBe(undefined);
    expect(log.error).toBeCalledWith('Invalid date given: "xxx". Format needs to be in "ISO 8601"');
});

test('Test valid example', () => {
    expect(dateFieldTransformer.transform('2018-03-10T14:09:04+01:00')).toBe('03/10/2018');
});

