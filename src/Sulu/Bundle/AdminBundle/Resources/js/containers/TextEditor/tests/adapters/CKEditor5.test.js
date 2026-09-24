// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import CKEditor5 from '../../adapters/CKEditor5';

let mockCKEditor5Props: Object = {};

const mockReact = require('react');

jest.mock('../../../CKEditor5', () => {
    return jest.fn((props) => {
        mockCKEditor5Props = props;

        return mockReact.createElement('div', {'data-testid': 'ckeditor5'});
    });
});

beforeEach(() => {
    jest.clearAllMocks();
    mockCKEditor5Props = {};
});

test('Pass correct props to CKEditor5 component', () => {
    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    const config = {enterMode: 'p', attributes: ['style'], tags: ['strong']};
    const locale = observable.box('en');

    render(
        <CKEditor5
            config={config}
            disabled={false}
            locale={locale}
            onBlur={blurSpy}
            onChange={changeSpy}
            value="Test"
        />
    );

    expect(mockCKEditor5Props).toEqual(expect.objectContaining({
        config,
        disabled: false,
        locale,
        onBlur: blurSpy,
        onChange: changeSpy,
        value: 'Test',
    }));
});
