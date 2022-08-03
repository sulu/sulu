// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Grid from '../Grid';

test('Render a Grid with Items in all sizes', () => {
    const {container} = render(
        <Grid>
            <Grid.Item colSpan={1} />
            <Grid.Item colSpan={2} />
            <Grid.Item colSpan={3} />
            <Grid.Item colSpan={4} />
            <Grid.Item colSpan={5} />
            <Grid.Item colSpan={6} />
            <Grid.Item colSpan={7} />
            <Grid.Item colSpan={8} />
            <Grid.Item colSpan={9} />
            <Grid.Item colSpan={10} />
            <Grid.Item colSpan={11} />
            <Grid.Item colSpan={12} />
        </Grid>
    );
    expect(container).toMatchSnapshot();
});

test('Render a Grid with Sections', () => {
    const {container} = render(
        <Grid>
            <Grid.Section colSpan={4}>
                <Grid.Item colSpan={1} />
                <Grid.Item colSpan={2} />
                <Grid.Item colSpan={3} />
                <Grid.Item colSpan={4} />
                <Grid.Item colSpan={5} />
                <Grid.Item colSpan={6} />
            </Grid.Section>
            <Grid.Section colSpan={8}>
                <Grid.Item colSpan={7} />
                <Grid.Item colSpan={8} />
                <Grid.Item colSpan={9} />
                <Grid.Item colSpan={10} />
                <Grid.Item colSpan={11} />
                <Grid.Item colSpan={12} />
            </Grid.Section>
        </Grid>
    );
    expect(container).toMatchSnapshot();
});

test('Render a Grid with Items having spaces between them', () => {
    const {container} = render(
        <Grid>
            <Grid.Item colSpan={4} spaceAfter={8} />
            <Grid.Item colSpan={2} spaceBefore={10} />
        </Grid>
    );
    expect(container).toMatchSnapshot();
});

test('Render a Grid with class names attached', () => {
    const {container} = render(
        <Grid className="test-grid">
            <Grid.Section className="test-section">
                <Grid.Item className="test-item" />
            </Grid.Section>
        </Grid>
    );
    expect(container).toMatchSnapshot();
});
