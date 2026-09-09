// @flow
import transformUrlToDate from '../transformUrlToDate';

test.each([
    ['1990-01-01', 1990, 0, 1, 0, 0],
    ['2005-12-31 23:59', 2005, 11, 31, 23, 59],
    ['2026-09-08 14:50', 2026, 8, 8, 14, 50],
])('Transform url value "%s"', (value, year, month, day, hour, minute) => {
    const date = transformUrlToDate(value);

    if (!date) {
        throw new Error('A date should be returned');
    }

    expect(date.getFullYear()).toEqual(year);
    expect(date.getMonth()).toEqual(month);
    expect(date.getDate()).toEqual(day);
    expect(date.getHours()).toEqual(hour);
    expect(date.getMinutes()).toEqual(minute);
});

test.each([
    [undefined],
    [null],
    [''],
    ['Dear'],
    ['1990'],
    ['01/01/1990'],
    ['1990-01-01T00:00:00.000Z'],
    ['1990-13-01'],
])('Return undefined for "%s"', (value) => {
    expect(transformUrlToDate(value)).toEqual(undefined);
});
