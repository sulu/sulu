// @flow
import transformDateForUrl from '../transformDateForUrl';

test.each([
    [new Date('2020-02-28'), '2020-02-28'],
    [new Date('2000-08-31'), '2000-08-31'],
    [new Date('2006-12-31'), '2006-12-31'],
    [new Date('1940-12-01'), '1940-12-01'],
])('Transform date "%s"', (date, expectedValue) => {
    expect(transformDateForUrl(date)).toEqual(expectedValue);
});
