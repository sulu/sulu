// @flow
import React from 'react';
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import CKEditor5 from '../../adapters/CKEditor5';

test('Pass correct props to CKEditor5 component', () => {
    const blurSpy = jest.fn();
    const changeSpy = jest.fn();

    const config = {enterMode: 'p', features: ['align'], tags: ['strong']};
    const locale = observable.box('en');

    const ckeditor5 = shallow(
        <CKEditor5
            config={config}
            disabled={false}
            locale={locale}
            onBlur={blurSpy}
            onChange={changeSpy}
            value="Test"
        />
    );

    expect(ckeditor5.find('CKEditor5').props()).toEqual(expect.objectContaining({
        config,
        disabled: false,
        locale,
        onBlur: blurSpy,
        onChange: changeSpy,
        value: 'Test',
    }));
});
