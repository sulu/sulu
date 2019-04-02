// @flow
import React from 'react';
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import CKEditor5 from '../../adapters/CKEditor5';

test('Pass correct props to CKEditor5 component', () => {
    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    const locale = observable.box('en');

    const ckeditor5 = shallow(
        <CKEditor5
            disabled={false}
            locale={locale}
            onBlur={blurSpy}
            onChange={changeSpy}
            options={{}}
            value="Test"
        />
    );

    expect(ckeditor5.find('CKEditor5').props()).toEqual(expect.objectContaining({
        disabled: false,
        formats: ['h2', 'h3', 'h4', 'h5', 'h6'],
        locale,
        onBlur: blurSpy,
        onChange: changeSpy,
        value: 'Test',
    }));
});

test('Pass formats to CKEditor5 component', () => {
    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    const options = {
        formats: {
            value: [
                {
                    name: 'h2',
                },
                {
                    name: 'h3',
                },
            ],
        },
    };

    const ckeditor5 = shallow(
        <CKEditor5
            disabled={false}
            locale={undefined}
            onBlur={blurSpy}
            onChange={changeSpy}
            options={options}
            value="Test"
        />
    );

    expect(ckeditor5.find('CKEditor5').props()).toEqual(expect.objectContaining({
        disabled: false,
        formats: ['h2', 'h3'],
        onBlur: blurSpy,
        onChange: changeSpy,
        value: 'Test',
    }));
});

test('Throw error if passed formats is not an array', () => {
    const options = {
        formats: {
            value: 'Test',
        },
    };

    expect(() =>
        shallow(
            <CKEditor5
                disabled={true}
                locale={undefined}
                onBlur={jest.fn()}
                onChange={jest.fn()}
                options={options}
                value={undefined}
            />
        )
    ).toThrow(/"formats" must be an array of strings/);
});

test('Throw error if passed formats is not an array', () => {
    const options = {
        formats: {
            value: [
                {
                    name: 'h2',
                },
                {
                    name: 3,
                },
            ],
        },
    };

    expect(() =>
        shallow(
            <CKEditor5
                disabled={true}
                locale={undefined}
                onBlur={jest.fn()}
                onChange={jest.fn()}
                options={options}
                value={undefined}
            />
        )
    ).toThrow(/"formats" must be strings/);
});
