// @flow
import transformTimeStringToDate from '../transformTimeStringToDate';

test.each([
    ['03:24:48', 3, 24, 48],
    ['12:10:00', 12, 10, 0],
    ['18:31:10', 18, 31, 10],
    ['10:39:00', 10, 39, 0],
])('Transform date "%s"', (time, hour, minute, second) => {
    const date = transformTimeStringToDate(time);

    if (!date) {
        throw new Error('A date should be returned');
    }

    expect(date.getHours()).toEqual(hour);
    expect(date.getMinutes()).toEqual(minute);
    expect(date.getSeconds()).toEqual(second);
});

test('Transform undefined', () => {
    expect(transformTimeStringToDate(undefined)).toEqual(undefined);
});
