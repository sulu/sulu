// @flow
import React from 'react';
import {mount} from 'enzyme';
import DateFieldFilterType from '../../fieldFilterTypes/DateFieldFilterType';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render with value of undefined', () => {
    const dateFieldFilterType = new DateFieldFilterType(jest.fn(), {}, undefined, {timeFormat: true});
    expect(mount(<div>{dateFieldFilterType.getFormNode()}</div>).render()).toMatchSnapshot();
});

test.each([
    [new Date('2020-01-02 12:00'), new Date('2020-01-09 13:00'), true],
    [new Date('2018-02-01 00:00'), new Date('2019-05-03 00:00'), true],
    [new Date('2020-01-02'), new Date('2020-01-09'), false],
    [new Date('2018-02-01'), new Date('2019-05-03'), false],
])('Render with from and to value', (from, to, timeFormatEnabled) => {
    const dateFieldFilterType = new DateFieldFilterType(
        jest.fn(),
        {},
        {from, to},
        {timeFormat: timeFormatEnabled}
    );
    expect(mount(<div>{dateFieldFilterType.getFormNode()}</div>).render()).toMatchSnapshot();
});

test.each([
    ['2017-06-03 12:00', '2018-03-06 12:00', true],
    ['2017-06-03', '2018-03-06', false],
])('Render with value set by setValue', (from, to, timeFormatEnabled) => {
    const dateFieldFilterType = new DateFieldFilterType(
        jest.fn(),
        {},
        undefined,
        {timeFormat: timeFormatEnabled}
    );

    dateFieldFilterType.setValue({from: new Date(from), to: new Date(to)});

    expect(mount(<div>{dateFieldFilterType.getFormNode()}</div>).render()).toMatchSnapshot();
});

test.each([
    ['2017-06-03 12:00', true],
    ['2017-06-03', false],
])('Call onChange handler with only from value', (from, timeFormatEnabled) => {
    const changeSpy = jest.fn();
    const dateFieldFilterType = new DateFieldFilterType(changeSpy, {}, undefined, {timeFormat: timeFormatEnabled});
    const dateFieldFilterTypeForm = mount(dateFieldFilterType.getFormNode());

    dateFieldFilterTypeForm.find('DatePicker').at(0).prop('onChange')(new Date(from));

    expect(changeSpy).toBeCalledWith({from: new Date(from)});
});

test.each([
    ['2017-06-03 12:00', true],
    ['2017-06-03', false],
])('Call onChange handler with only to value', (to, timeFormatEnabled) => {
    const changeSpy = jest.fn();
    const dateFieldFilterType = new DateFieldFilterType(changeSpy, {}, undefined, {timeFormat: timeFormatEnabled});
    const dateFieldFilterTypeForm = mount(dateFieldFilterType.getFormNode());

    dateFieldFilterTypeForm.find('DatePicker').at(1).prop('onChange')(new Date(to));

    expect(changeSpy).toBeCalledWith({to: new Date(to)});
});

test.each([
    ['2018-03-07 00:00', '2018-03-07 12:00', '2018-08-02 12:00', true],
    ['2018-03-07', '2018-03-08', '2018-08-02', false],
])('Call onChange handler with from value and existing value', (
    fromActual,
    fromExpected,
    to,
    timeFormatEnabled
) => {
    const changeSpy = jest.fn();
    const dateFieldFilterType = new DateFieldFilterType(
        changeSpy,
        {},
        {from: new Date(fromActual), to: new Date(to)},
        {timeFormat: timeFormatEnabled}
    );
    const dateFieldFilterTypeForm = mount(dateFieldFilterType.getFormNode());

    dateFieldFilterTypeForm.find('DatePicker').at(0).prop('onChange')(new Date(fromExpected));

    expect(changeSpy).toBeCalledWith({from: new Date(fromExpected), to: new Date(to)});
});

test.each([
    ['2018-03-07 12:00', '2018-08-02 00:00', '2018-08-02 12:00', true],
    ['2018-03-07', '2018-08-02', '2018-08-03', false],
])('Call onChange handler with to value and existing value', (
    from,
    toActual,
    toExpected,
    timeFormatEnabled
) => {
    const changeSpy = jest.fn();
    const dateFieldFilterType = new DateFieldFilterType(
        changeSpy,
        {},
        {from: new Date(from), to: new Date(toActual)},
        {timeFormat: timeFormatEnabled}
    );
    const dateFieldFilterTypeForm = mount(dateFieldFilterType.getFormNode());

    dateFieldFilterTypeForm.find('DatePicker').at(1).prop('onChange')(new Date(toExpected));

    expect(changeSpy).toBeCalledWith({from: new Date(from), to: new Date(toExpected)});
});

test.each([
    [
        {from: new Date('2018-03-07 12:00'), to: new Date('2018-08-02 12:00')},
        '03/07/2018, 12:00 PM - 08/02/2018, 12:00 PM',
        true,
    ],
    [
        {from: new Date('2017-11-07 12:00'), to: new Date('2017-12-11 12:00')},
        '11/07/2017, 12:00 PM - 12/11/2017, 12:00 PM',
        true,
    ],
    [{from: new Date('1990-11-07 12:00')}, 'sulu_admin.from 11/07/1990, 12:00 PM', true],
    [{to: new Date('1992-12-04 12:00')}, 'sulu_admin.until 12/04/1992, 12:00 PM', true],
    [undefined, null, true],
    [{}, null, true],
    [{from: new Date('2018-03-07'), to: new Date('2018-08-02')}, '03/07/2018 - 08/02/2018', false],
    [{from: new Date('2017-11-07'), to: new Date('2017-12-11')}, '11/07/2017 - 12/11/2017', false],
    [{from: new Date('1990-11-07')}, 'sulu_admin.from 11/07/1990', false],
    [{to: new Date('1992-12-04')}, 'sulu_admin.until 12/04/1992', false],
    [undefined, null, false],
    [{}, null, false],
])('Return value node with value "%s"', (value, expectedValueNode, timeFormatEnabled) => {
    const dateFieldFilterType = new DateFieldFilterType(jest.fn(), {}, undefined, {timeFormat: timeFormatEnabled});

    const valueNodePromise = dateFieldFilterType.getValueNode(value);

    if (!valueNodePromise) {
        throw new Error('The getValueNode function must return a promise!');
    }

    return valueNodePromise.then((valueNode) => {
        expect(valueNode).toEqual(expectedValueNode);
    });
});
