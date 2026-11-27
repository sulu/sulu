// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Section from '../Section';

test('Render section with given colSpan', () => {
    const {container} = render(
        <Section label="Test">
            <p>Test</p>
        </Section>
    );
    expect(container).toMatchSnapshot();
});

test('Render section without label', () => {
    const {container} = render(
        <Section colSpan={8}>
            <div>Test</div>
        </Section>
    );
    expect(container).toMatchSnapshot();
});

test('Render section without label but with divider', () => {
    const {container} = render(
        <Section>
            <p>Test</p>
        </Section>
    );
    expect(container).toMatchSnapshot();
});

test('Render section with description', () => {
    const {container} = render(
        <Section description="Section description" label="Test">
            <p>Test</p>
        </Section>
    );
    expect(container).toMatchSnapshot();
});

test('Render section with description but without label', () => {
    const {container} = render(
        <Section colSpan={6} description="Section description">
            <p>Test</p>
        </Section>
    );
    expect(container).toMatchSnapshot();
});
