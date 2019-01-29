// @flow
import React from 'react';
import {render} from 'enzyme';
import Section from '../Section';

test('Render section with given size', () => {
    expect(render(
        <Section label="Test">
            <p>Test</p>
        </Section>
    )).toMatchSnapshot();
});

test('Render section without label', () => {
    expect(render(
        <Section size={8}>
            <div>Test</div>
        </Section>
    )).toMatchSnapshot();
});

test('Render section without label but with divider', () => {
    expect(render(
        <Section>
            <p>Test</p>
        </Section>
    )).toMatchSnapshot();
});
