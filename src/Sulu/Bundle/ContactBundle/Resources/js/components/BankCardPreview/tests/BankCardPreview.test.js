//@flow
import React from 'react';
import {render} from 'enzyme';
import BankCardPreview from '../BankCardPreview';

test('Render BankCardPreview without bank name', () => {
    expect(render(
        <BankCardPreview bankName={undefined} bic="GIBAATWGXXX" iban="AT483200000012345864" />
    )).toMatchSnapshot();
});

test('Render BankCardPreview with bank name', () => {
    expect(render(
        <BankCardPreview bankName="Testbank" bic="GIBAATWGXXX" iban="AT483200000012345864" />
    )).toMatchSnapshot();
});
