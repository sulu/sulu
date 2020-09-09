// @flow
import transformDateForUrl from '../transformDateForUrl';

test.each([
    [new Date('2020-02-28'), '2020-02-28 00:00'],
    [new Date('2000-08-31 12:00'), '2000-08-31 12:00'],
    [new Date('2006-12-31'), '2006-12-31 00:00'],
    [new Date('1940-12-01 12:00'), '1940-12-01 12:00'],
])('Transform date "%s"', (date, expectedValue) => {
    expect(transformDateForUrl(date)).toEqual(expectedValue);
});
