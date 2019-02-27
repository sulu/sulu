// @flow
import React from 'react';
import {render} from 'enzyme';
import Grid from '../Grid';

test('Render a Grid with Items in all sizes', () => {
    expect(render(
        <Grid>
            <Grid.Item colspan={1} />
            <Grid.Item colspan={2} />
            <Grid.Item colspan={3} />
            <Grid.Item colspan={4} />
            <Grid.Item colspan={5} />
            <Grid.Item colspan={6} />
            <Grid.Item colspan={7} />
            <Grid.Item colspan={8} />
            <Grid.Item colspan={9} />
            <Grid.Item colspan={10} />
            <Grid.Item colspan={11} />
            <Grid.Item colspan={12} />
        </Grid>
    )).toMatchSnapshot();
});

test('Render a Grid with Sections', () => {
    expect(render(
        <Grid>
            <Grid.Section colspan={4}>
                <Grid.Item colspan={1} />
                <Grid.Item colspan={2} />
                <Grid.Item colspan={3} />
                <Grid.Item colspan={4} />
                <Grid.Item colspan={5} />
                <Grid.Item colspan={6} />
            </Grid.Section>
            <Grid.Section colspan={8}>
                <Grid.Item colspan={7} />
                <Grid.Item colspan={8} />
                <Grid.Item colspan={9} />
                <Grid.Item colspan={10} />
                <Grid.Item colspan={11} />
                <Grid.Item colspan={12} />
            </Grid.Section>
        </Grid>
    )).toMatchSnapshot();
});

test('Render a Grid with Items having spaces between them', () => {
    expect(render(
        <Grid>
            <Grid.Item colspan={4} spaceAfter={8} />
            <Grid.Item colspan={2} spaceBefore={10} />
        </Grid>
    )).toMatchSnapshot();
});

test('Render a Grid with class names attached', () => {
    expect(render(
        <Grid className="test-grid">
            <Grid.Section className="test-section">
                <Grid.Item className="test-item" />
            </Grid.Section>
        </Grid>
    )).toMatchSnapshot();
});
