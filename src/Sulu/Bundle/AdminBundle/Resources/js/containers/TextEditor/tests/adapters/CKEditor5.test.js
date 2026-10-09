// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import CKEditor5 from '../../adapters/CKEditor5';

let mockCKEditor5Props: Object = {};

const mockReact = require('react');

jest.mock('../../../CKEditor5', () => {
    const CKEditor5Mock: any = jest.fn((props) => {
        mockCKEditor5Props = props;

        return mockReact.createElement('div', {'data-testid': 'ckeditor5'});
    });

    CKEditor5Mock.defaultProps = {
        formats: ['h2', 'h3', 'h4', 'h5', 'h6'],
    };

    return CKEditor5Mock;
});

beforeEach(() => {
    jest.clearAllMocks();
    mockCKEditor5Props = {};
});

function expectRenderToThrow(element, error) {
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    expect(() => render(element)).toThrow(error);

    consoleErrorSpy.mockRestore();
}

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

    expect(mockCKEditor5Props).toEqual(expect.objectContaining({
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

    expect(mockCKEditor5Props).toEqual(expect.objectContaining({
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

    expectRenderToThrow(
        <CKEditor5
            disabled={true}
            locale={undefined}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            options={options}
            value={undefined}
        />,
        /"formats" must be an array of strings/
    );
});

test('Throw error if passed formats contains a non-string name', () => {
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

    expectRenderToThrow(
        <CKEditor5
            disabled={true}
            locale={undefined}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            options={options}
            value={undefined}
        />,
        /"formats" must be strings/
    );
});
