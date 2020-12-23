// @flow
import transformDateToTimeString from '../transformDateToTimeString';

test.each([
    [new Date('2020-02-28 03:24:48'), '03:24:48'],
    [new Date('2000-08-31 12:10:00'), '12:10:00'],
    [new Date('2006-12-31 18:31:10'), '18:31:10'],
    [new Date('1940-12-01 10:39'), '10:39:00'],
    [undefined, undefined],
])('Transform date "%s"', (date, expectedValue) => {
    expect(transformDateToTimeString(date)).toEqual(expectedValue);
});
