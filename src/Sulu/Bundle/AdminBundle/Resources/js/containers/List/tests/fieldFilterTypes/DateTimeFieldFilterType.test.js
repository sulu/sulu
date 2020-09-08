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
    [new Date('2020-01-02 12:00'), new Date('2020-01-09 13:00')],
    [new Date('2018-02-01 00:00'), new Date('2019-05-03 00:00')],
])('Render with from and to value', (from, to) => {
    const dateFieldFilterType = new DateFieldFilterType(
        jest.fn(),
        {},
        {from, to},
        {timeFormat: true}
    );
    expect(mount(<div>{dateFieldFilterType.getFormNode()}</div>).render()).toMatchSnapshot();
});

test('Render with value set by setValue', () => {
    const dateFieldFilterType = new DateFieldFilterType(
        jest.fn(),
        {},
        undefined,
        {timeFormat: true}
    );

    dateFieldFilterType.setValue({from: new Date('2017-06-03 12:00'), to: new Date('2018-03-06 12:00')});

    expect(mount(<div>{dateFieldFilterType.getFormNode()}</div>).render()).toMatchSnapshot();
});

test('Call onChange handler with only from value', () => {
    const changeSpy = jest.fn();
    const dateFieldFilterType = new DateFieldFilterType(changeSpy, {}, undefined, {timeFormat: true});
    const dateFieldFilterTypeForm = mount(dateFieldFilterType.getFormNode());

    dateFieldFilterTypeForm.find('DatePicker').at(0).prop('onChange')(new Date('2018-03-06 12:00'));

    expect(changeSpy).toBeCalledWith({from: new Date('2018-03-06 12:00')});
});

test('Call onChange handler with only to value', () => {
    const changeSpy = jest.fn();
    const dateFieldFilterType = new DateFieldFilterType(changeSpy, {}, undefined, {timeFormat: true});
    const dateFieldFilterTypeForm = mount(dateFieldFilterType.getFormNode());

    dateFieldFilterTypeForm.find('DatePicker').at(1).prop('onChange')(new Date('2018-04-06 12:00'));

    expect(changeSpy).toBeCalledWith({to: new Date('2018-04-06 12:00')});
});

test('Call onChange handler with from value and existing value', () => {
    const changeSpy = jest.fn();
    const dateFieldFilterType = new DateFieldFilterType(
        changeSpy,
        {},
        {from: new Date('2018-03-07'), to: new Date('2018-08-02 12:00')},
        {timeFormat: true}
    );
    const dateFieldFilterTypeForm = mount(dateFieldFilterType.getFormNode());

    dateFieldFilterTypeForm.find('DatePicker').at(0).prop('onChange')(new Date('2017-12-25 12:00'));

    expect(changeSpy).toBeCalledWith({from: new Date('2017-12-25 12:00'), to: new Date('2018-08-02 12:00')});
});

test('Call onChange handler with to value and existing value', () => {
    const changeSpy = jest.fn();
    const dateFieldFilterType = new DateFieldFilterType(
        changeSpy,
        {},
        {from: new Date('2018-03-07 12:00'), to: new Date('2018-08-02')},
        {timeFormat: true}
    );
    const dateFieldFilterTypeForm = mount(dateFieldFilterType.getFormNode());

    dateFieldFilterTypeForm.find('DatePicker').at(1).prop('onChange')(new Date('2018-04-06 12:00'));

    expect(changeSpy).toBeCalledWith({from: new Date('2018-03-07 12:00'), to: new Date('2018-04-06 12:00')});
});

test.each([
    [
        {from: new Date('2018-03-07 12:00'), to: new Date('2018-08-02 12:00')},
        '03/07/2018, 12:00 PM - 08/02/2018, 12:00 PM',
    ],
    [
        {from: new Date('2017-11-07 12:00'), to: new Date('2017-12-11 12:00')},
        '11/07/2017, 12:00 PM - 12/11/2017, 12:00 PM',
    ],
    [{from: new Date('1990-11-07 12:00')}, 'sulu_admin.from 11/07/1990, 12:00 PM'],
    [{to: new Date('1992-12-04 12:00')}, 'sulu_admin.until 12/04/1992, 12:00 PM'],
    [undefined, null],
    [{}, null],
])('Return value node with value "%s"', (value, expectedValueNode) => {
    const dateFieldFilterType = new DateFieldFilterType(jest.fn(), {}, undefined, {timeFormat: true});

    const valueNodePromise = dateFieldFilterType.getValueNode(value);

    if (!valueNodePromise) {
        throw new Error('The getValueNode function must return a promise!');
    }

    return valueNodePromise.then((valueNode) => {
        expect(valueNode).toEqual(expectedValueNode);
    });
});
