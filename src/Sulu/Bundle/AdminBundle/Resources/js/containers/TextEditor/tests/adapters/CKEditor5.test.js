// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import CKEditor5Component from '../../../CKEditor5';
import getLatestMockProps from '../../../../utils/TestHelper/getLatestMockProps';
import CKEditor5 from '../../adapters/CKEditor5';

jest.mock('../../../CKEditor5', () => {
    const CKEditor5Component: any = jest.fn(() => null);
    CKEditor5Component.defaultProps = {
        formats: ['h2', 'h3', 'h4', 'h5', 'h6'],
    };

    return CKEditor5Component;
});

test('Pass correct props to CKEditor5 component', () => {
    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    const locale = observable.box('en');
    render(
        <CKEditor5
            disabled={false}
            locale={locale}
            onBlur={blurSpy}
            onChange={changeSpy}
            options={{}}
            value="Test"
        />
    );

    const ckeditor5Props: any = getLatestMockProps((CKEditor5Component: any));
    expect(ckeditor5Props).toEqual(expect.objectContaining({
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
            name: 'formats',
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
    render(
        <CKEditor5
            disabled={false}
            locale={undefined}
            onBlur={blurSpy}
            onChange={changeSpy}
            options={options}
            value="Test"
        />
    );

    const ckeditor5Props: any = getLatestMockProps((CKEditor5Component: any));
    expect(ckeditor5Props).toEqual(expect.objectContaining({
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
            name: 'formats',
            value: 'Test',
        },
    };

    expect(() => render(
        <CKEditor5
            disabled={true}
            locale={undefined}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            options={options}
            value={undefined}
        />
    )).toThrow(/"formats" must be an array of strings/);
});

test('Throw error if passed formats is not an array', () => {
    const options = {
        formats: {
            name: 'formats',
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

    expect(() => render(
        <CKEditor5
            disabled={true}
            locale={undefined}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            options={options}
            value={undefined}
        />
    )).toThrow(/"formats" must be strings/);
});
