// @flow
import {mount} from 'enzyme';
import DateTimeFieldFilterType from '../../fieldFilterTypes/DateTimeFieldFilterType';

test('Render with value of undefined', () => {
    const dateTimeFieldFilterType = new DateTimeFieldFilterType(jest.fn(), {}, undefined);
    expect(mount(dateTimeFieldFilterType.getFormNode()).render()).toMatchSnapshot();
});

test.each([
    [new Date('2020-01-02'), new Date('2020-01-09')],
    [new Date('2018-02-01'), new Date('2019-05-03')],
])('Render with from and to value', (from, to) => {
    const dateTimeFieldFilterType = new DateTimeFieldFilterType(
        jest.fn(),
        {},
        {from, to}
    );
    expect(mount(dateTimeFieldFilterType.getFormNode()).render()).toMatchSnapshot();
});

test('Render with value set by setValue', () => {
    const dateTimeFieldFilterType = new DateTimeFieldFilterType(
        jest.fn(),
        {},
        undefined
    );

    dateTimeFieldFilterType.setValue({from: new Date('2017-06-03'), to: new Date('2018-03-06')});

    expect(mount(dateTimeFieldFilterType.getFormNode()).render()).toMatchSnapshot();
});

test('Call onChange handler with only from value', () => {
    const changeSpy = jest.fn();
    const dateTimeFieldFilterType = new DateTimeFieldFilterType(changeSpy, {}, undefined);
    const dateTimeFieldFilterTypeForm = mount(dateTimeFieldFilterType.getFormNode());

    dateTimeFieldFilterTypeForm.find('DatePicker').at(0).prop('onChange')(new Date('2018-03-06'));

    expect(changeSpy).toBeCalledWith({from: new Date('2018-03-06')});
});

test('Call onChange handler with only to value', () => {
    const changeSpy = jest.fn();
    const dateTimeFieldFilterType = new DateTimeFieldFilterType(changeSpy, {}, undefined);
    const dateTimeFieldFilterTypeForm = mount(dateTimeFieldFilterType.getFormNode());

    dateTimeFieldFilterTypeForm.find('DatePicker').at(1).prop('onChange')(new Date('2018-04-06'));

    expect(changeSpy).toBeCalledWith({to: new Date('2018-04-06')});
});

test('Call onChange handler with from value and existing value', () => {
    const changeSpy = jest.fn();
    const dateTimeFieldFilterType = new DateTimeFieldFilterType(
        changeSpy,
        {},
        {from: new Date('2018-03-07'), to: new Date('2018-08-02')}
    );
    const dateTimeFieldFilterTypeForm = mount(dateTimeFieldFilterType.getFormNode());

    dateTimeFieldFilterTypeForm.find('DatePicker').at(0).prop('onChange')(new Date('2017-12-25'));

    expect(changeSpy).toBeCalledWith({from: new Date('2017-12-25'), to: new Date('2018-08-02')});
});

test('Call onChange handler with to value and existing value', () => {
    const changeSpy = jest.fn();
    const dateTimeFieldFilterType = new DateTimeFieldFilterType(
        changeSpy,
        {},
        {from: new Date('2018-03-07'), to: new Date('2018-08-02')}
    );
    const dateTimeFieldFilterTypeForm = mount(dateTimeFieldFilterType.getFormNode());

    dateTimeFieldFilterTypeForm.find('DatePicker').at(1).prop('onChange')(new Date('2018-04-06'));

    expect(changeSpy).toBeCalledWith({from: new Date('2018-03-07'), to: new Date('2018-04-06')});
});

test.each([
    [{from: new Date('2018-03-07'), to: new Date('2018-08-02')}, '03/07/2018 - 08/02/2018'],
    [{from: new Date('2017-11-07'), to: new Date('2017-12-11')}, '11/07/2017 - 12/11/2017'],
    [undefined, undefined],
])('Return value node with value "%s"', (value, expectedValueNode) => {
    const dateTimeFieldFilterType = new DateTimeFieldFilterType(jest.fn(), {}, undefined);

    const valueNodePromise = dateTimeFieldFilterType.getValueNode(value);

    if (expectedValueNode === undefined) {
        expect(valueNodePromise).toEqual(null);
        return;
    }

    if (!valueNodePromise) {
        throw new Error('The getValueNode function must return a promise!');
    }

    return valueNodePromise.then((valueNode) => {
        expect(valueNode).toEqual(expectedValueNode);
    });
});
