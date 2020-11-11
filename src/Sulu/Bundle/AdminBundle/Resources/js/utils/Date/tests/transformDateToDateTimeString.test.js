// @flow
import transformDateToDateTimeString from '../transformDateToDateTimeString';

test.each([
    [new Date('2020-02-28 03:24:48'), '2020-02-28T03:24:48'],
    [new Date('2000-08-31 12:10:00'), '2000-08-31T12:10:00'],
    [new Date('2006-12-31 18:31:10'), '2006-12-31T18:31:10'],
    [new Date('1940-12-01 10:39:00'), '1940-12-01T10:39:00'],
    [undefined, undefined],
])('Transform date "%s"', (date, expectedValue) => {
    expect(transformDateToDateTimeString(date)).toEqual(expectedValue);
});
