//@flow
import React from 'react';
import {render} from '@testing-library/react';
import BankCardPreview from '../BankCardPreview';

test('Render BankCardPreview without bank name', () => {
    const {asFragment} = render(
        <BankCardPreview bankName={undefined} bic="GIBAATWGXXX" iban="AT483200000012345864" />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render BankCardPreview with bank name', () => {
    const {asFragment} = render(
        <BankCardPreview bankName="Testbank" bic="GIBAATWGXXX" iban="AT483200000012345864" />
    );

    expect(asFragment()).toMatchSnapshot();
});
