// @flow
import React from 'react';
import {render} from 'enzyme';
import Grid from '../Grid';

test('Render a Grid with Items in all sizes', () => {
    expect(render(
        <Grid>
            <Grid.Item size={1} />
            <Grid.Item size={2} />
            <Grid.Item size={3} />
            <Grid.Item size={4} />
            <Grid.Item size={5} />
            <Grid.Item size={6} />
            <Grid.Item size={7} />
            <Grid.Item size={8} />
            <Grid.Item size={9} />
            <Grid.Item size={10} />
            <Grid.Item size={11} />
            <Grid.Item size={12} />
        </Grid>
    )).toMatchSnapshot();
});

test('Render a Grid with Sections', () => {
    expect(render(
        <Grid>
            <Grid.Section size={4}>
                <Grid.Item size={1} />
                <Grid.Item size={2} />
                <Grid.Item size={3} />
                <Grid.Item size={4} />
                <Grid.Item size={5} />
                <Grid.Item size={6} />
            </Grid.Section>
            <Grid.Section size={8}>
                <Grid.Item size={7} />
                <Grid.Item size={8} />
                <Grid.Item size={9} />
                <Grid.Item size={10} />
                <Grid.Item size={11} />
                <Grid.Item size={12} />
            </Grid.Section>
        </Grid>
    )).toMatchSnapshot();
});

test('Render a Grid with Items having spaces between them', () => {
    expect(render(
        <Grid>
            <Grid.Item size={4} spaceAfter={8} />
            <Grid.Item size={2} spaceBefore={10} />
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
